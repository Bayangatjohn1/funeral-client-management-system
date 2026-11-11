<?php
namespace App\Support;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
final class CatalogFormResponse
{
    public static function saved(Request $request, string $message, string $fallback): Response|RedirectResponse
    {
        if ($request->boolean('_service_modal')) {
            return response()->view('admin.service-management.partials.saved-frame', ['message' => $message]);
        }
        return redirect($request->input('return_to') ?: $fallback)->with('success', $message);
    }
}
