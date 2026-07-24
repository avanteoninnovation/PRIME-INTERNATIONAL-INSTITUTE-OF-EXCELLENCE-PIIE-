@php
    $printTarget = $printTarget ?? 'page-print-area';
@endphp

<div class="page-toolbar print-hidden d-flex flex-wrap justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
        {{ get_phrase('Print') }}
    </button>
    <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportCurrentPagePdf('{{ $printTarget }}')">
        {{ get_phrase('PDF') }}
    </button>
    <button type="button" class="btn btn-outline-success btn-sm page-toolbar-excel" onclick="exportCurrentPageExcel('{{ $printTarget }}')">
        {{ get_phrase('Excel') }}
    </button>
</div>

@once
    <style>
        @media print {
            .print-hidden,
            .page-toolbar,
            .sidebar,
            .home-header,
            .copyright-text,
            .navbar,
            .modal,
            .modal-backdrop {
                display: none !important;
            }

            .home-section,
            #app,
            .main_content {
                left: 0 !important;
                width: 100% !important;
                margin-left: 0 !important;
            }

            body {
                background: #fff !important;
            }
        }
    </style>

    <script>
        (function () {
            function exportFileBaseName() {
                var title = (document.title || 'export').toLowerCase();
                return title.replace(/[^a-z0-9]+/gi, '-').replace(/^-+|-+$/g, '') || 'export';
            }

            function getPrintableRoot(targetId) {
                return document.getElementById(targetId || 'page-print-area');
            }

            function removeToolbarNodes(root) {
                if (!root) {
                    return;
                }

                root.querySelectorAll('.page-toolbar, .print-hidden, .no-print').forEach(function (node) {
                    node.remove();
                });
            }

            window.exportCurrentPagePdf = function (targetId) {
                var source = getPrintableRoot(targetId);

                if (!source) {
                    window.print();
                    return;
                }

                if (typeof window.html2pdf === 'undefined') {
                    window.print();
                    return;
                }

                var cloned = source.cloneNode(true);
                removeToolbarNodes(cloned);

                var wrapper = document.createElement('div');
                wrapper.appendChild(cloned);

                html2pdf().set({
                    margin: 0.5,
                    filename: exportFileBaseName() + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                }).from(wrapper).save();
            };

            window.exportCurrentPageExcel = function (targetId) {
                var source = getPrintableRoot(targetId);

                if (!source) {
                    return;
                }

                var table = source.querySelector('table');
                if (!table) {
                    alert("{{ get_phrase('No table available to export.') }}");
                    return;
                }

                var html = '<html><head><meta charset="utf-8"></head><body>' + table.outerHTML + '</body></html>';
                var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
                var url = window.URL.createObjectURL(blob);
                var link = document.createElement('a');

                link.href = url;
                link.download = exportFileBaseName() + '.xls';
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            };

            document.addEventListener('DOMContentLoaded', function () {
                var excelButton = document.querySelector('.page-toolbar-excel');
                var pageArea = document.getElementById('page-print-area');

                if (excelButton && (!pageArea || !pageArea.querySelector('table'))) {
                    excelButton.style.display = 'none';
                }
            });
        })();
    </script>
@endonce