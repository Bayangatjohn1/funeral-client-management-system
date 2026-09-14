<style>
    .svc-detail {
        max-width: 980px;
        color: #1f2d20;
    }

    .svc-detail-card {
        overflow: hidden;
        border: 1px solid #B7C9AE;
        border-radius: 10px;
        background: #EEF4EA;
        box-shadow: 0 14px 30px rgba(31, 45, 32, 0.08);
    }

    .svc-detail-head,
    .svc-detail-section,
    .svc-detail-actions {
        padding: 22px;
    }

    .svc-detail-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        border-bottom: 1px solid #C7D7BE;
        background: #E3EEDC;
    }

    .svc-detail-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: #60705D;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .svc-detail h1,
    .svc-detail h2,
    .svc-detail h3,
    .svc-detail p {
        margin: 0;
    }

    .svc-detail h1 {
        font-size: clamp(1.5rem, 2vw, 2rem);
        line-height: 1.15;
    }

    .svc-detail-subtitle {
        margin-top: 8px;
        color: #60705D;
        font-weight: 700;
    }

    .svc-detail-badges,
    .svc-detail-actions,
    .svc-detail-stat-grid,
    .svc-detail-list-grid {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .svc-detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 7px 12px;
        border: 1px solid #B7C9AE;
        border-radius: 999px;
        background: #F8FAF4;
        color: #43513F;
        font-size: 0.82rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .svc-detail-badge.is-active {
        border-color: #B6D9AC;
        background: #E4F2DF;
        color: #2F5E34;
    }

    .svc-detail-badge.is-muted {
        color: #7C6E48;
        border-color: #D6C9A0;
        background: #F3ECD4;
    }

    .svc-detail-primary,
    .svc-detail-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 8px;
        font-weight: 800;
        text-decoration: none;
        transition: border-color 160ms ease, background 160ms ease, color 160ms ease, box-shadow 160ms ease;
    }

    .svc-detail-primary {
        border: 1px solid #284A2D;
        background: #284A2D;
        color: #fff;
        box-shadow: 0 12px 22px rgba(40, 74, 45, 0.18);
    }

    .svc-detail-secondary {
        border: 1px solid #B7C9AE;
        background: #F8FAF4;
        color: #43513F;
    }

    .svc-detail-primary:hover,
    .svc-detail-primary:focus-visible {
        background: #203D24;
        color: #fff;
    }

    .svc-detail-secondary:hover,
    .svc-detail-secondary:focus-visible {
        border-color: #78906E;
        color: #284A2D;
    }

    .svc-detail-primary:focus-visible,
    .svc-detail-secondary:focus-visible {
        outline: 3px solid rgba(47, 81, 49, 0.3);
        outline-offset: 2px;
    }

    .svc-detail-stat-grid {
        padding: 18px 22px;
        border-bottom: 1px solid #C7D7BE;
        background: #DCE8D4;
    }

    .svc-detail-stat {
        flex: 1 1 190px;
        min-width: 0;
        padding: 16px;
        border: 1px solid #B7C9AE;
        border-radius: 8px;
        background: rgba(248, 250, 244, 0.72);
    }

    .svc-detail-stat span,
    .svc-detail-section h3 {
        display: block;
        color: #60705D;
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .svc-detail-stat strong {
        display: block;
        margin-top: 8px;
        font-size: clamp(1.25rem, 2vw, 1.65rem);
        line-height: 1.1;
    }

    .svc-detail-section {
        border-bottom: 1px solid #C7D7BE;
    }

    .svc-detail-list-grid {
        margin-top: 14px;
    }

    .svc-detail-list {
        flex: 1 1 260px;
        min-width: 0;
        padding: 16px;
        border: 1px solid #C7D7BE;
        border-radius: 8px;
        background: #F8FAF4;
    }

    .svc-detail-list ul {
        display: grid;
        gap: 10px;
        margin: 12px 0 0;
        padding: 0;
        list-style: none;
    }

    .svc-detail-list li {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        color: #344232;
        font-weight: 700;
    }

    .svc-detail-list li::before {
        content: "";
        width: 7px;
        height: 7px;
        margin-top: 8px;
        border-radius: 999px;
        background: #2F8D5B;
        flex: 0 0 auto;
    }

    .svc-detail-empty {
        margin-top: 12px;
        color: #60705D;
        font-style: italic;
        font-weight: 700;
    }

    .svc-detail-note {
        margin-top: 12px;
        color: #43513F;
        line-height: 1.55;
        font-weight: 650;
    }

    .svc-detail-actions {
        justify-content: flex-end;
        background: #DCE8D4;
    }

    @media (max-width: 720px) {
        .svc-detail-head {
            display: grid;
        }

        .svc-detail-actions {
            display: grid;
        }
    }
</style>
