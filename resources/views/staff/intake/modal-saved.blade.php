<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Intake saved</title>
</head>
<body style="background:transparent">
    <p>Intake saved. <a id="intake-saved-destination" href="{{ $destination }}" target="_parent">Continue</a></p>
    <script>
        const destination = document.getElementById('intake-saved-destination');
        const embedded = window.parent !== window && window.frameElement?.hasAttribute('data-intake-frame');
        const stayOnPage = @json($stayOnPage ?? false);
        if (embedded && stayOnPage) {
            destination.parentElement.hidden = true;
            window.parent.showPanelToast?.(@json($message ?? 'Historical entry submitted for review.'), 'success');
            window.parent.closeStaffIntake?.(window);
        } else if (window.parent === window || embedded) {
            if (embedded && new URL(destination.href).pathname.endsWith('/intake/saved')) {
                destination.parentElement.hidden = true;
                window.parent.prepareStaffIntakeSuccess?.(window);
                const url = new URL(destination.href);
                url.searchParams.set('intake_modal', '1');
                window.location.replace(url.href);
            } else destination.click();
        }
    </script>
</body>
</html>
