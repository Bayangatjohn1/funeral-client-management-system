<?php

namespace App\Support;

use App\Models\{AddOnCatalog, CasketCatalog, FreebieCatalog, HearseCatalog, Package, Promotion};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ServiceBrochure
{
    public const TABS = ['packages' => 'Service Packages', 'caskets' => 'Casket Collection', 'hearses' => 'Hearse Collection',
        'addons' => 'Additional Services', 'freebies' => 'Complimentary Inclusions', 'promotions' => 'Current Offers'];

    private function eligible(Builder $query, string $tab): Builder
    {
        if ($tab === 'packages') {
            return app(ServiceCatalogPolicy::class)->intakePackages($query)
                ->whereDoesntHave('packageInclusions', fn ($q) => $q->whereNotNull('add_on_catalog_id')->whereDoesntHave('addOnCatalog', fn ($a) => $a->where('is_active', true)->where('is_available', true)));
        }
        if ($tab === 'promotions') return $query->whereHas('package', fn ($q) => app(ServiceCatalogPolicy::class)->intakePackages($q));
        return $query->where('is_active', true)->where('is_available', true);
    }

    public function preview(Request $request, string $tab, Builder $query, array $options)
    {
        abort_unless($request->user()?->isMainAdmin(), 403);
        $models = ['packages'=>Package::class, 'caskets'=>CasketCatalog::class, 'hearses'=>HearseCatalog::class,
            'addons'=>AddOnCatalog::class, 'freebies'=>FreebieCatalog::class, 'promotions'=>Promotion::class];
        $load = ['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageInclusions.addOnCatalog', 'packageFreebies.catalog', 'promotion'];
        if ($tab === 'packages') $query->with($load);
        if ($tab === 'promotions') $query->with('package');
        $rows = $this->eligible(clone $query, $tab)->get();
        $choiceQuery = $models[$tab]::query()->orderBy('name')->orderBy('id');
        if ($tab === 'promotions') $choiceQuery->with('package');
        $choices = $this->eligible($choiceQuery, $tab)->get();
        if ($tab === 'promotions') {
            $active = fn ($p) => $p->isEffectiveAt(now('Asia/Manila'), $p->package);
            $rows = $rows->filter($active)->values(); $choices = $choices->filter($active)->values();
            $rows = match ($request->input('sort', 'name_asc')) {
                'name_desc' => $rows->sortByDesc('name'), 'latest' => $rows->sortByDesc('created_at'),
                'oldest' => $rows->sortBy('created_at'), 'discount_asc' => $rows->sortBy('discount_value'),
                'discount_desc' => $rows->sortByDesc('discount_value'), 'start_date' => $rows->sortBy('starts_at'),
                'end_date' => $rows->sortBy('ends_at'), default => $rows->sortBy('name'),
            };
        }
        $scope = $options['scope'] ?? 'all';
        $ids = array_map('intval', $options['ids'] ?? []);
        $notice = null;
        if ($scope === 'selected') {
            if (!$ids || array_diff($ids, $choices->modelKeys())) $notice = 'Some selected items are no longer available. Only available items are included. Review your selection before printing.';
            $ids = array_values(array_intersect($ids, $choices->modelKeys()));
            $rows = $rows->whereIn('id', $ids)->values();
        }
        $items = $rows->map(fn ($row) => $this->item($row, $tab))->values();
        $missingPhotoCount = $tab === 'packages' ? $items->filter(fn ($item) => !$item['photos'])->count() : 0;
        $photoNotice = $missingPhotoCount
            ? trans_choice('{1} :count package has no photo.|[2,*] :count packages have no photos.', $missingPhotoCount).' Add a photo in Edit Package to include it in print.'
            : null;
        $filters = $request->only(['q', 'status', 'availability', 'package_status', 'promotion_status', 'sort']);
        return response()->view('admin.service-management.print', [
            'tab'=>$tab, 'scope'=>$scope, 'ids'=>$ids, 'choices'=>$choices, 'items'=>$items, 'filters'=>$filters, 'notice'=>$notice,
            'title'=>self::TABS[$tab], 'preparedAt'=>now('Asia/Manila'),
            'branch'=>$request->user()->branch, 'photoNotice'=>$photoNotice,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function item($record, string $tab): array
    {
        $item = ['name'=>$record->name, 'description'=>$record->description, 'photos'=>[], 'sections'=>[], 'price'=>null, 'price_label'=>'', 'tag'=>null];
        if ($tab !== 'promotions' && ($url=CatalogPhotos::url($record))) $item['photos'][]=['url'=>$url,'alt'=>$record->name];
        if ($tab === 'packages') return $this->package($record, $item);
        if ($tab === 'caskets') {
            $item['price']=(float)$record->standard_price>0 ? $record->standard_price : null; $item['price_label']='Reference value';
            if ($record->type_or_material) $item['sections']['Specifications']=[['label'=>'Material / type','value'=>$record->type_or_material]];
        } elseif ($tab === 'hearses') {
            $item['price']=$record->reference_value; $item['price_label']='Reference value';
        } elseif ($tab === 'addons') {
            $item['price']=$record->price; $item['price_label']='per '.($record->unit ?: 'item');
            $item['tag']=$record->category ?: 'Service';
            if ($record->category === AddOnCatalog::CHAPEL_VIEWING && $record->chapel_address) $item['sections']['Location']=[['label'=>'Address','value'=>$record->chapel_address]];
        } elseif ($tab === 'freebies') {
            $item['tag']='Complimentary';
            $item['sections']['Item details']=[['label'=>'Unit','value'=>$record->default_unit ?: 'item']];
        } else {
            $item['tag']='Current offer';
            $item['sections']['Offer']=[['label'=>'Discount','value'=>$this->offer($record->discount_type,$record->discount_value)], ['label'=>'Package','value'=>$record->package?->name ?: '']];
            $item['sections']['Validity']=$this->dates($record->starts_at,$record->ends_at);
        }
        return $item;
    }

    private function package(Package $package, array $item): array
    {
        $item['description']=$package->short_description;
        $item['price']=$package->price; $item['price_label']='Base package price';
        $services=[]; $others=[]; $rates=[];
        foreach ($package->packageInclusions as $included) {
            if (!$included->add_on_catalog_id && Package::isLegacyPackageDescription($included->inclusion_name)) continue;
            $label=Package::serviceTypeOptions()[$included->service_type] ?? ($included->inclusion_name ?: 'Included service');
            $value='Included';
            if ($included->included_kilometers !== null) $value=$this->number($included->included_kilometers).' km included';
            if ($included->included_days !== null) $value=$included->included_days.' days / '.max(0,$included->included_days-1).' nights included';
            if ($included->service_type === Package::SERVICE_EMBALMING && $included->included_days !== null) $value=$included->included_days.' days included';
            $asset=match($included->service_type) { Package::SERVICE_CASKET=>$included->casketCatalog, Package::SERVICE_HEARSE=>$included->hearseCatalog, default=>null };
            if ($asset) {
                $value=($asset->display_name ?? $asset->name).($value !== 'Included' ? ' · '.$value : ' · Included');
            } elseif ($included->service_type===Package::SERVICE_CASKET && $included->casket_type) $value=$included->casket_type.' · Included';
            if ($included->service_type===Package::SERVICE_CUSTOM || $included->add_on_catalog_id) {
                $addon=$included->addOnCatalog;
                $label=$addon?->name ?: $included->inclusion_name;
                if ($included->included_quantity !== null) $value=$included->included_quantity.' '.($addon?->unit ?: 'item').' included';
                $others[]=['label'=>$label,'value'=>$value];
                if ($addon && (float)$addon->price>0) $rates[]=['label'=>$label,'value'=>'₱'.number_format((float)$addon->price,2).' / extra '.($addon->unit ?: 'item')];
            } else $services[]=['label'=>$label,'value'=>$value];
            if ((float)$included->price_per_excess_kilometer>0) $rates[]=['label'=>$label,'value'=>'₱'.number_format((float)$included->price_per_excess_kilometer,2).' / extra km'];
            if ((float)$included->price_per_extended_day>0) $rates[]=['label'=>$label,'value'=>'₱'.number_format((float)$included->price_per_extended_day,2).' / extra day'];
        }
        if (!$services && !$others) foreach ($package->inclusionNames() as $label) if (!Package::isLegacyPackageDescription($label)) $services[]=['label'=>$label,'value'=>'Included'];
        if (!$package->packageInclusions->contains('service_type',Package::SERVICE_CASKET) && filled($package->coffin_type)) {
            $casketRow=collect($services)->search(fn ($row)=>preg_match('/^(?:casket|coffin)(?:\W|$)/i',$row['label']));
            if ($casketRow === false) $services[]=['label'=>'Casket','value'=>$package->coffin_type.' · Included'];
            elseif (in_array(mb_strtolower(trim($services[$casketRow]['label'])),['casket','coffin'],true)) $services[$casketRow]['value']=$package->coffin_type.' · Included';
        }
        if ($services) $item['sections']['Included Services']=$services;
        if ($others) $item['sections']['Other Inclusions']=$others;
        $freebies=$package->packageFreebies->map(fn ($f)=>['label'=>$f->catalog?->name ?: $f->freebie_name,'value'=>($f->quantity ?? 1).' '.($f->unit ?: $f->catalog?->default_unit ?: 'item').' included'])->all();
        if (!$freebies) foreach ($package->freebieNames() as $label) $freebies[]=['label'=>$label,'value'=>'Included'];
        if ($freebies) $item['sections']['Freebies']=$freebies;
        if ($rates) $item['sections']['Additional Charges']=$rates;
        if ($package->isPromoEffectiveAt(now('Asia/Manila'))) {
            $p=$package->promotion;
            $item['sections']['Current Promotion']=[['label'=>$p?->name ?: $package->promo_label,'value'=>$this->offer($p?->discount_type ?? $package->promo_value_type,$p?->discount_value ?? $package->promo_value)],
                ...$this->dates($p?->starts_at ?? $package->promo_starts_at,$p?->ends_at ?? $package->promo_ends_at)];
        }
        return $item;
    }

    private function dates($start,$end): array
    {
        $value = $start && $end ? $start->format('M d, Y').' — '.$end->format('M d, Y')
            : ($end ? 'Until '.$end->format('M d, Y') : ($start ? 'From '.$start->format('M d, Y').' · until withdrawn' : 'While this offer remains active'));
        return [['label'=>'Valid','value'=>$value]];
    }

    private function offer($type,$value): string
    {
        return strtoupper((string)$type)==='PERCENT' ? $this->number($value).'% off the base package price' : '₱'.number_format((float)$value,2).' off the base package price';
    }

    private function number($value): string { return rtrim(rtrim(number_format((float)$value,2,'.',''),'0'),'.'); }
}
