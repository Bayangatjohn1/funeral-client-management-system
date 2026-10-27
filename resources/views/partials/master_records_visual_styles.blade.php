    <style>
        .master-records-page {
            --records-card: #D3DEC9;
            --records-card-alt: #DCE6D6;
            --records-card-strong: #C7D5BE;
            --records-hover: #C5D3BC;
            --records-active: #B8C9AF;
            --records-border: #AEBBA8;
            --records-border-strong: #8EA083;
            --records-text: #232821;
            --records-muted: #3F4C3E;
            --records-row: #EEF3E8;
            --records-row-alt: #E5EDDF;
            min-height:calc(100vh - 1rem);
            padding:.9rem var(--panel-content-inline) 20px;
            color:var(--records-text);
            font-family:var(--font-body);
            background:transparent;
        }

        html[data-theme='dark'] .master-records-page {
            --records-card: #112218;
            --records-card-alt: #14261D;
            --records-card-strong: #1B3527;
            --records-hover: #1B3527;
            --records-active: #244F3B;
            --records-border: #29523D;
            --records-border-strong: #41634F;
            --records-text: #F8FAFC;
            --records-muted: #B5C7BC;
            --records-row: #112218;
            --records-row-alt: #14261D;
        }

        .master-records-page,
        .master-records-page * {
            box-shadow:none !important;
            filter:none !important;
            backdrop-filter:none !important;
        }

        .master-records-toast {
            position:fixed;
            top:1rem;
            right:1rem;
            z-index:1200;
            display:flex;
            align-items:center;
            gap:.55rem;
            max-width:calc(100vw - 2rem);
            border:1px solid var(--records-border-strong);
            border-radius:8px;
            background:#2F3A2E;
            color:#F7FAF3;
            padding:.72rem .9rem;
            font-size:.88rem;
            font-weight:650;
            line-height:1.35;
            pointer-events:none;
            animation:masterRecordsToastIn .18s ease-out, masterRecordsToastOut .22s ease-in 3.8s forwards;
        }

        .master-records-toast i {
            color:#DCE6D6;
        }

        @keyframes masterRecordsToastIn {
            from { opacity:0; transform:translateY(-.35rem); }
            to { opacity:1; transform:translateY(0); }
        }

        @keyframes masterRecordsToastOut {
            to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
        }

        .master-records-page a[href],
        .master-records-page button,
        .master-records-page select,
        .master-records-page [role="button"],
        .master-records-page [data-clickable-row] {
            cursor:pointer;
            transition:background-color .14s ease,border-color .14s ease,color .14s ease;
        }

        .master-records-page input {
            transition:background-color .14s ease,border-color .14s ease,color .14s ease;
        }

        .master-records-page a:focus,
        .master-records-page button:focus,
        .master-records-page select:focus,
        .master-records-page input:focus,
        .master-records-page [data-clickable-row]:focus {
            outline:none !important;
        }

        .master-records-page .table-system-card,
        .master-records-page .admin-table-card {
            background:transparent !important;
            border:0 !important;
            border-radius:8px !important;
            overflow:visible;
        }

        .master-records-page .table-system-toolbar,
        .master-records-page .case-records-master-chip-row,
        .master-records-page .table-system-list,
        .master-records-page .table-system-wrap,
        .master-records-page .table-system-pagination,
        .master-records-page .case-records-tabs {
            background:var(--records-card) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
            color:var(--records-text) !important;
        }

        .master-records-page .table-system-toolbar {
            padding:.9rem !important;
            margin-bottom:.75rem;
            overflow:visible;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter,
        .master-records-page .admin-table-toolbar .case-compact-search-row,
        .master-records-page .admin-table-toolbar .case-compact-filter-bar,
        .master-records-page .admin-table-toolbar .case-compact-advanced {
            background:transparent !important;
            border:0 !important;
            padding:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter {
            display:flex !important;
            flex-wrap:wrap;
            align-items:center;
            gap:.7rem;
        }

        .master-records-page .admin-table-toolbar .case-compact-search-row {
            display:contents !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-search-field {
            order:1 !important;
            flex:1 1 320px !important;
            display:block !important;
            width:auto !important;
            min-width:0 !important;
            max-width:none !important;
            height:auto !important;
            min-height:0 !important;
            align-self:end;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
            padding:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter-bar {
            order:2 !important;
            display:flex !important;
            align-items:center;
            justify-content:flex-start;
            flex-wrap:wrap;
            gap:.6rem;
            width:auto !important;
            min-height:2.85rem;
            min-width:0 !important;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
            padding:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-filter-bar > * {
            flex-shrink:0 !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-actions {
            order:3 !important;
            display:flex !important;
            align-items:center;
            justify-content:flex-end;
            gap:.55rem;
            width:auto !important;
            margin-left:auto !important;
            flex:0 0 auto !important;
        }

        .master-records-page .admin-table-toolbar .case-compact-advanced {
            order:3 !important;
            position:fixed !important;
            inset:0 !important;
            z-index:1200 !important;
            display:flex !important;
            justify-content:flex-end !important;
            width:100% !important;
            flex:none !important;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
            padding:0 !important;
            overflow:visible;
        }

        .master-records-page .admin-table-toolbar .case-compact-advanced[hidden] {
            display:none !important;
        }

        .master-records-page .case-compact-advanced-grid {
            display:grid;
            grid-template-columns:1fr;
            gap:.7rem;
            align-items:end;
        }

        .master-records-page .case-compact-advanced-actions {
            display:flex;
            justify-content:flex-end;
            gap:.55rem;
            margin:.1rem -.85rem -.85rem;
            padding:.75rem .85rem;
            border-top:1px solid var(--records-border);
            background:var(--records-card);
        }

        .master-records-page .case-compact-search-field {
            position:relative;
        }

        .master-records-page .case-compact-search-field label {
            display:none !important;
        }

        .master-records-page .case-compact-search-field::before {
            display:none !important;
            content:none !important;
        }

        .master-records-page .case-compact-search-field .case-compact-input {
            padding-left:2.65rem !important;
            width:100% !important;
            min-height:2.85rem !important;
            height:2.85rem !important;
            display:block !important;
            background:var(--filter-ui-bg, #f7f8f1) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
        }

        .master-records-page .case-compact-field label,
        .master-records-page .case-compact-pop-field label,
        .master-records-page .table-system-table thead th {
            color:var(--records-muted) !important;
            font-size:.72rem !important;
            font-weight:650 !important;
            letter-spacing:.04em !important;
            text-transform:uppercase;
            opacity:1 !important;
        }

        .master-records-page .case-compact-input,
        .master-records-page .case-compact-date-select,
        .master-records-page .case-compact-sort-select,
        .master-records-page .case-compact-branch,
        .master-records-page .case-compact-seg,
        .master-records-page .case-compact-more,
        .master-records-page .case-compact-reset,
        .master-records-page .case-compact-pop-input,
        .master-records-page .case-compact-pop-reset,
        .master-records-page .case-compact-advanced-clear,
        .master-records-page .case-compact-chip {
            background:var(--filter-ui-bg, #f7f8f1) !important;
            border:1px solid var(--records-border) !important;
            color:var(--records-text) !important;
            border-radius:8px !important;
            font-weight:650 !important;
        }

        .master-records-page .case-compact-branch,
        .master-records-page .case-compact-seg,
        .master-records-page .case-compact-more,
        .master-records-page .case-compact-reset,
        .master-records-page .case-compact-apply {
            min-height:2.85rem !important;
            height:2.85rem !important;
        }

        .master-records-page .case-compact-branch {
            display:flex !important;
            align-items:center;
            flex:0 0 16.5rem !important;
            width:16.5rem !important;
            min-width:16.5rem !important;
            max-width:16.5rem !important;
            padding:0 2.35rem 0 .9rem !important;
        }

        .master-records-page .case-compact-seg {
            position:relative;
            display:inline-flex !important;
            align-items:center;
            flex:0 0 12rem !important;
            width:12rem !important;
            min-width:12rem !important;
            max-width:12rem !important;
            padding:0 !important;
        }

        .master-records-page .case-compact-more,
        .master-records-page .case-compact-reset,
        .master-records-page .case-compact-apply {
            flex:0 0 auto !important;
        }

        .master-records-page .case-compact-date-select,
        .master-records-page .case-compact-sort-select {
            width:100% !important;
            height:100% !important;
            min-height:0 !important;
            padding:0 2.25rem 0 2.45rem !important;
            appearance:none;
        }

        .master-records-page .case-compact-select,
        .master-records-page .case-compact-date-select,
        .master-records-page .case-compact-sort-select {
            background:transparent !important;
            border:0 !important;
            box-shadow:none !important;
        }

        .master-records-page .case-compact-select {
            width:100% !important;
            min-height:0 !important;
            height:100% !important;
            padding:0 !important;
            appearance:none;
        }

        .master-records-page .case-compact-date-icon,
        .master-records-page .case-compact-sort-icon,
        .master-records-page .case-compact-date-chev,
        .master-records-page .case-compact-sort-chev,
        .master-records-page .case-compact-select-chev {
            pointer-events:none;
            color:var(--records-muted);
        }

        .master-records-page .case-compact-date-icon,
        .master-records-page .case-compact-sort-icon {
            position:absolute;
            left:.9rem;
            z-index:1;
        }

        .master-records-page .case-compact-date-chev,
        .master-records-page .case-compact-sort-chev {
            position:absolute;
            top:50%;
            right:.85rem;
            transform:translateY(-50%);
            z-index:1;
        }

        .master-records-page .case-compact-apply,
        .master-records-page .case-compact-pop-apply {
            background:#344333 !important;
            border:1px solid #344333 !important;
            color:#fff !important;
            border-radius:8px !important;
            font-weight:700 !important;
        }

        .master-records-page .case-compact-input:hover,
        .master-records-page .case-compact-date-select:hover,
        .master-records-page .case-compact-sort-select:hover,
        .master-records-page .case-compact-seg:hover,
        .master-records-page .case-compact-branch:hover,
        .master-records-page .case-compact-more:hover,
        .master-records-page .case-compact-reset:hover,
        .master-records-page .case-compact-pop-reset:hover,
        .master-records-page .case-compact-advanced-clear:hover,
        .master-records-page .case-compact-chip:hover {
            background:var(--records-card-strong) !important;
            border-color:var(--records-border-strong) !important;
            color:var(--records-text) !important;
        }

        .master-records-page .case-compact-apply:hover,
        .master-records-page .case-compact-pop-apply:hover {
            background:#2F3A2E !important;
            border-color:#2F3A2E !important;
            color:#fff !important;
        }

        .master-records-page .case-compact-popover {
            background:var(--records-card-alt) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
        }

        .master-records-page .case-records-tabs-row {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.75rem;
            flex-wrap:wrap;
            margin:0 0 .75rem;
            padding:0;
            background:transparent;
            border:0;
        }

        .master-records-page .case-records-tabs {
            display:inline-flex;
            gap:.35rem;
            padding:.35rem;
            background:var(--records-card-alt) !important;
        }

        .master-records-page .table-quick-tab {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:2.45rem;
            padding:0 .95rem;
            border:1px solid transparent;
            border-radius:8px;
            color:var(--records-muted);
            font-size:.85rem;
            font-weight:650;
            text-decoration:none;
        }

        .master-records-page .table-quick-tab:hover {
            background:var(--records-card-strong);
            color:var(--records-text);
        }

        .master-records-page .table-quick-tab-active {
            background:var(--records-active);
            border-color:var(--records-border-strong);
            color:var(--records-text);
        }

        .master-records-page .case-records-master-chip-row {
            display:flex;
            justify-content:flex-start;
            padding:0;
            margin:0;
            background:transparent !important;
            border:0 !important;
            flex:1 1 320px;
        }

        .master-records-page .case-compact-inline-chips {
            display:flex;
            flex-wrap:wrap;
            justify-content:flex-end;
            gap:.45rem;
            width:100%;
        }

        .master-records-page .case-records-master-chip-row .case-compact-chip {
            max-width:min(340px, 100%);
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .master-records-page .table-system-list {
            padding:.75rem;
            overflow:hidden;
        }

        @media (max-width: 1000px) {
            .master-records-page .admin-table-toolbar .case-compact-filter {
                display:flex !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-search-row,
            .master-records-page .admin-table-toolbar .case-compact-actions {
                width:100% !important;
                justify-content:flex-start;
            }

            .master-records-page .case-compact-branch,
            .master-records-page .case-compact-seg,
            .master-records-page .case-compact-more {
                flex-basis:100% !important;
                width:100% !important;
                max-width:none !important;
            }

            .master-records-page .case-records-tabs-row {
                align-items:flex-start;
            }

            .master-records-page .case-records-master-chip-row,
            .master-records-page .case-compact-inline-chips {
                justify-content:flex-start;
            }

            .master-records-page .case-compact-advanced-grid {
                grid-template-columns:1fr;
            }

            .master-records-page .case-compact-advanced-actions {
                flex-direction:column;
                align-items:stretch;
            }
        }

        .master-records-page .table-system-wrap,
        .master-records-page .table-system-table {
            background:var(--records-card-alt) !important;
        }

        .master-records-page .table-system-wrap {
            overflow-x:hidden !important;
            overflow-y:visible !important;
        }

        .master-records-page .table-system-table {
            width:100%;
            min-width:0 !important;
            border-collapse:separate;
            border-spacing:0;
            table-layout:fixed;
        }

        .master-records-page .records-worklist-table .records-col-case { width:14%; }
        .master-records-page .records-worklist-table .records-col-family { width:21%; }
        .master-records-page .records-worklist-table .records-col-service { width:13%; }
        .master-records-page .records-worklist-table .records-col-schedule { width:12%; }
        .master-records-page .records-worklist-table .records-col-financials { width:17%; }
        .master-records-page .records-worklist-table .records-col-case-status { width:8%; }
        .master-records-page .records-worklist-table .records-col-payment-status { width:10%; }
        .master-records-page .records-worklist-table .records-col-actions { width:5%; }

        .master-records-page .table-system-table .table-primary,
        .master-records-page .table-system-table .table-secondary {
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .master-records-page .table-system-table thead th {
            background:var(--records-card-strong) !important;
            padding:.95rem 1rem !important;
        }

        .master-records-page .table-system-table tbody tr {
            background:var(--records-row);
        }

        .master-records-page .table-system-table tbody tr:nth-child(even) {
            background:var(--records-row-alt);
        }

        .master-records-page .table-system-table tbody tr:hover {
            background:var(--records-hover) !important;
        }

        .master-records-page .table-system-table td {
            padding:1rem !important;
            border-color:var(--records-border) !important;
            color:var(--records-text);
        }

        .master-records-page .table-primary {
            color:var(--records-text) !important;
            font-weight:680 !important;
            letter-spacing:0 !important;
        }

        .master-records-page .table-secondary {
            color:var(--records-muted) !important;
            font-weight:600 !important;
            opacity:1 !important;
        }

        .master-records-page .records-case-code {
            font-size:.98rem;
        }

        .master-records-page .row-needs-attention {
            border-left:4px solid #A85248;
        }

        .master-records-page .table-system-empty {
            background:#E1E7D9 !important;
            color:var(--records-muted) !important;
            font-weight:650;
            text-align:center;
            padding:2rem !important;
        }

        .master-records-page #adminCaseViewOverlay {
            background:var(--modal-scrim) !important;
            backdrop-filter:none !important;
            -webkit-backdrop-filter:none !important;
        }

        .master-records-page #adminCaseViewSheet {
            background:var(--records-card) !important;
            border:1px solid var(--records-border) !important;
            border-radius:8px !important;
        }

        .master-records-page #adminCaseViewContent {
            background:var(--records-card) !important;
        }

        .admin-master-toolbar-row-primary {
            grid-template-columns: minmax(280px, 2.2fr) repeat(2, minmax(180px, 1fr)) auto;
        }

        .admin-master-primary-actions {
            justify-content: flex-end;
            align-items: flex-end;
            gap: 8px;
        }

        .admin-master-more-btn {
            min-height: 40px;
            white-space: nowrap;
            min-width: 132px;
        }

        .admin-master-more-btn i {
            font-size: 13px;
        }

        .admin-master-more-filters {
            margin-top: 8px;
        }

        .admin-master-more-filters .admin-master-toolbar-row-bottom {
            grid-template-columns: repeat(4, minmax(150px, 1fr)) repeat(2, minmax(150px, 180px));
        }

        .admin-master-active-summary {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #5F685F;
        }

        .admin-master-active-summary strong {
            color: #333333;
            font-weight: 700;
        }

        .admin-master-active-summary a {
            color: #333333;
            font-weight: 600;
        }

        .admin-master-active-summary a:hover {
            color: #3E4A3D;
            text-decoration: underline;
        }

        @media (max-width: 1200px) {
            .admin-master-toolbar-row-primary {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }

            .admin-master-primary-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 680px) {
            .admin-master-toolbar-row-primary {
                grid-template-columns: 1fr;
            }

            .admin-master-primary-actions {
                width: 100%;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 767px) {
            .master-records-page,
            .master-records-page *,
            .master-records-page *::before,
            .master-records-page *::after {
                box-sizing:border-box;
            }

            .master-records-page {
                max-width:100vw;
                overflow-x:clip;
                padding-inline:clamp(.5rem, 3vw, .75rem) !important;
            }

            .master-records-page .table-system-toolbar,
            .master-records-page .admin-table-toolbar,
            .master-records-page .case-compact-filter,
            .master-records-page .case-compact-search-row,
            .master-records-page .case-compact-filter-bar,
            .master-records-page .case-compact-actions,
            .master-records-page .case-records-tabs-row,
            .master-records-page .case-records-tabs,
            .master-records-page .table-system-list,
            .master-records-page .table-system-wrap {
                width:100% !important;
                max-width:100% !important;
                min-width:0 !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-filter {
                display:grid !important;
                grid-template-columns:minmax(0, 1fr);
                gap:.65rem !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-search-row {
                display:grid !important;
                grid-template-columns:minmax(0, 1fr);
                gap:.55rem !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-filter-bar {
                display:grid !important;
                grid-template-columns:minmax(0, 1fr);
                gap:.55rem !important;
                align-items:stretch;
                justify-content:stretch !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-search-field,
            .master-records-page .case-compact-search-control,
            .master-records-page .case-compact-input,
            .master-records-page .case-compact-branch,
            .master-records-page .case-compact-seg,
            .master-records-page .case-compact-more,
            .master-records-page .case-compact-reset,
            .master-records-page .case-compact-apply {
                width:100% !important;
                max-width:100% !important;
                min-width:0 !important;
                flex-basis:auto !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-filter-bar > *,
            .master-records-page .admin-table-toolbar .case-compact-branch,
            .master-records-page .admin-table-toolbar .case-compact-seg,
            .master-records-page .admin-table-toolbar .case-compact-more {
                justify-self:stretch !important;
                flex:1 1 auto !important;
            }

            .master-records-page .admin-table-toolbar .case-compact-select,
            .master-records-page .admin-table-toolbar .case-compact-date-select,
            .master-records-page .admin-table-toolbar .case-compact-sort-select {
                width:100% !important;
                max-width:100% !important;
                min-width:0 !important;
            }

            .master-records-page .case-records-tabs {
                display:grid !important;
                grid-template-columns:repeat(4, minmax(0, 1fr));
                gap:.35rem;
                padding:.35rem;
            }

            .master-records-page .table-quick-tab {
                width:100%;
                min-width:0;
                padding-inline:.25rem;
                overflow-wrap:anywhere;
                text-align:center;
                white-space:normal;
            }

            .master-records-page .table-system-list {
                padding:.55rem;
            }

            .master-records-page .table-system-wrap {
                margin:0 !important;
                overflow:visible !important;
            }

            .master-records-page .admin-master-table colgroup,
            .master-records-page .admin-master-table thead {
                display:none;
            }

            .master-records-page .admin-master-table,
            .master-records-page .admin-master-table tbody,
            .master-records-page .admin-master-table tr,
            .master-records-page .admin-master-table td {
                display:block;
                width:100% !important;
                max-width:100% !important;
                min-width:0 !important;
            }

            .master-records-page .admin-master-table {
                border-collapse:separate;
                border-spacing:0;
                table-layout:auto;
            }

            .master-records-page .admin-master-table tbody {
                display:grid;
                gap:.7rem;
            }

            .master-records-page .admin-master-table tbody tr {
                border:1px solid var(--records-border);
                border-radius:8px;
                overflow:hidden;
                background:var(--records-card-alt) !important;
            }

            .master-records-page .admin-master-table .row-needs-attention {
                border-left:0;
                box-shadow:inset 4px 0 0 #A85248 !important;
            }

            .master-records-page .admin-master-table tbody td {
                display:grid;
                grid-template-columns:minmax(5.6rem, .4fr) minmax(0, 1fr);
                gap:.75rem;
                align-items:start;
                padding:.65rem .8rem !important;
                border:0 !important;
                border-bottom:1px solid var(--records-border) !important;
                background:transparent !important;
                text-align:left !important;
            }

            .master-records-page .admin-master-table tbody td:last-child {
                border-bottom:0 !important;
            }

            .master-records-page .admin-master-table tbody td::before {
                content:attr(data-label);
                grid-column:1;
                grid-row:1 / span 3;
                color:var(--records-muted);
                font-size:.7rem;
                font-weight:800;
                line-height:1.25;
            }

            .master-records-page .admin-master-table tbody td > * {
                grid-column:2;
                min-width:0;
            }

            .master-records-page .admin-master-table .table-primary,
            .master-records-page .admin-master-table .table-secondary,
            .master-records-page .admin-master-table .table-financial-total,
            .master-records-page .admin-master-table .table-financial-breakdown {
                min-width:0;
                overflow:visible;
                overflow-wrap:anywhere;
                text-overflow:clip;
                white-space:normal !important;
                text-align:left !important;
            }

            .master-records-page .admin-master-table .status-badge,
            .master-records-page .admin-master-table .table-payment-status-badge {
                justify-self:start;
                max-width:100%;
                white-space:normal;
            }
        }

        @media (max-width: 380px) {
            .master-records-page .case-records-tabs {
                grid-template-columns:repeat(2, minmax(0, 1fr));
            }
        }
    </style>
