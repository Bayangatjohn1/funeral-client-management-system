<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Catalog saved</title></head><body>
<script>
if (window.parent !== window) {
    window.parent.postMessage({ type: 'service-catalog:saved', message: {{ Illuminate\Support\Js::from($message) }} }, window.location.origin);
}
</script>
</body></html>
