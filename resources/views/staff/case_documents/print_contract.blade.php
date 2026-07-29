<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Funeral Contract</title>
    <style>
        * { box-sizing: border-box; }
        html,
        body {
            height: 100%;
            margin: 0;
            background: #111827;
            color: #f8fafc;
            font-family: Arial, sans-serif;
        }
        .print-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            background: #1f2937;
            border-bottom: 1px solid #374151;
        }
        .print-title {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
        }
        .print-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .print-button,
        .print-link {
            border: 1px solid #475569;
            background: #f8fafc;
            color: #111827;
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .print-link {
            background: transparent;
            color: #f8fafc;
        }
        .print-frame {
            display: block;
            width: 100%;
            height: calc(100% - 48px);
            border: 0;
            background: #fff;
        }
        @media print {
            .print-toolbar { display: none; }
            .print-frame {
                height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <p class="print-title">Preparing Funeral Contract for printing...</p>
        <div class="print-actions">
            <button type="button" class="print-button" id="printButton">Print</button>
            <a class="print-link" href="{{ $pdfUrl }}" target="_blank" rel="noopener">Open PDF</a>
        </div>
    </div>

    <iframe id="printFrame" class="print-frame" src="{{ $pdfUrl }}" title="Funeral Contract PDF"></iframe>

    <script>
        (function () {
            const frame = document.getElementById('printFrame');
            const button = document.getElementById('printButton');

            const printFrame = () => {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (error) {
                    window.print();
                }
            };

            button.addEventListener('click', printFrame);
            frame.addEventListener('load', () => {
                window.setTimeout(printFrame, 450);
            });
        })();
    </script>
</body>
</html>
