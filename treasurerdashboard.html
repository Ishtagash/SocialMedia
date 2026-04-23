<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Treasurer Dashboard</title>
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <style>
      :root {
        --navy: #051650;
        --navy-mid: #0a2160;
        --lime: #ccff00;
        --lime-dim: #aadd00;
        --surface: #ffffff;
        --page-bg: #f2f4f8;
        --border-color: #e3e7f0;
        --text-main: #1a2240;
        --text-soft: #7a869a;
        --green: #22c55e;
        --green-bg: #f0fdf4;
        --green-text: #166534;
        --blue: #3b82f6;
        --blue-bg: #eff6ff;
        --blue-text: #1e40af;
        --amber: #f59e0b;
        --amber-bg: #fffbeb;
        --amber-text: #92400e;
        --card-radius: 14px;
        --card-shadow: 0 1px 4px rgba(5, 22, 80, 0.07);
        --card-shadow-lg: 0 6px 20px rgba(5, 22, 80, 0.12);
        --sidebar-width: 228px;
        --topbar-height: 62px;
      }

      *,
      *::before,
      *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        font-family: "DM Sans", sans-serif;
        background: var(--page-bg);
        color: var(--text-main);
        min-height: 100vh;
        overflow-x: hidden;
      }

      @keyframes slide-in-left {
        from {
          opacity: 0;
          transform: translateX(-22px);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      @keyframes fade-down {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes fade-up {
        from {
          opacity: 0;
          transform: translateY(16px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .treasurerdashboard-page {
        display: flex;
        min-height: 100vh;
      }

      /* SIDEBAR */
      .treasurerdashboard-sidebar {
        width: var(--sidebar-width);
        background: var(--navy);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 40;
        animation: slide-in-left 0.4s cubic-bezier(0.22, 0.68, 0, 1.1) both;
      }

      .treasurerdashboard-brand-row {
        height: var(--topbar-height);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
      }

      .treasurerdashboard-brand-logo {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--lime);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
      }

      .treasurerdashboard-brand-logo i {
        font-size: 13px;
        color: var(--navy);
      }
      .treasurerdashboard-brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .treasurerdashboard-brand-name {
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
      }
      .treasurerdashboard-brand-sub {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.4);
      }

      .treasurerdashboard-nav-list {
        flex: 1;
        padding: 14px 10px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        overflow-y: auto;
      }

      .treasurerdashboard-nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.07);
        margin: 7px 8px;
      }

      .treasurerdashboard-nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 13px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.55);
        text-decoration: none;
        transition: all 0.15s ease;
        cursor: pointer;
      }

      .treasurerdashboard-nav-link:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
      }
      .treasurerdashboard-nav-link.active {
        background: var(--lime);
        color: var(--navy);
      }
      .treasurerdashboard-nav-text {
        flex: 1;
      }

      .treasurerdashboard-sidebar-bottom {
        padding: 12px 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.07);
      }

      .treasurerdashboard-logout-link {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 13px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.4);
        background: transparent;
        border: none;
        cursor: pointer;
        width: 100%;
        font-family: inherit;
        text-decoration: none;
        transition: all 0.15s;
      }

      .treasurerdashboard-logout-link i {
        font-size: 12px;
      }
      .treasurerdashboard-logout-link:hover {
        background: rgba(220, 38, 38, 0.12);
        color: #fca5a5;
      }

      /* MAIN */
      .treasurerdashboard-main {
        margin-left: var(--sidebar-width);
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        min-width: 0;
      }

      /* TOPBAR */
      .treasurerdashboard-topbar {
        height: var(--topbar-height);
        background: var(--surface);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 0 24px;
        position: sticky;
        top: 0;
        z-index: 30;
        animation: fade-down 0.36s ease both;
      }

      .treasurerdashboard-topbar-logo {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--navy);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
      }

      .treasurerdashboard-topbar-logo i {
        font-size: 12px;
        color: var(--lime);
      }
      .treasurerdashboard-topbar-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .treasurerdashboard-topbar-search {
        flex: 1;
        max-width: 380px;
        height: 36px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        background: var(--page-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        transition: border-color 0.18s;
      }

      .treasurerdashboard-topbar-search:focus-within {
        border-color: var(--navy);
        background: var(--surface);
      }
      .treasurerdashboard-topbar-search i {
        color: var(--text-soft);
        font-size: 12px;
        flex-shrink: 0;
      }

      .treasurerdashboard-topbar-search input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-family: inherit;
        font-size: 13px;
        color: var(--text-main);
      }

      .treasurerdashboard-topbar-search input::placeholder {
        color: var(--text-soft);
      }

      .treasurerdashboard-topbar-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .treasurerdashboard-notif-btn {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: var(--page-bg);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-soft);
        font-size: 13px;
        cursor: pointer;
        position: relative;
        text-decoration: none;
        transition: all 0.15s;
      }

      .treasurerdashboard-notif-btn:hover {
        border-color: var(--navy);
        color: var(--navy);
      }

      .treasurerdashboard-notif-dot {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 7px;
        height: 7px;
        background: var(--green);
        border-radius: 50%;
        border: 1.5px solid var(--surface);
      }

      .treasurerdashboard-profile-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px 4px 5px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        background: var(--page-bg);
        cursor: pointer;
        transition: border-color 0.15s;
      }

      .treasurerdashboard-profile-chip:hover {
        border-color: var(--navy);
      }

      .treasurerdashboard-profile-avatar {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: var(--navy);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        color: var(--lime);
        overflow: hidden;
        flex-shrink: 0;
      }

      .treasurerdashboard-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .treasurerdashboard-profile-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
      }
      .treasurerdashboard-profile-role {
        font-size: 11px;
        color: var(--text-soft);
        line-height: 1;
      }

      /* BODY */
      .treasurerdashboard-body {
        padding: 22px 24px 48px;
        display: flex;
        flex-direction: column;
        gap: 20px;
      }

      /* BANNER */
      .treasurerdashboard-banner {
        background: var(--navy);
        border-radius: var(--card-radius);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        animation: fade-up 0.4s 0.14s ease both;
      }

      .treasurerdashboard-banner-text h2 {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
      }
      .treasurerdashboard-banner-text p {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 4px;
      }
      .treasurerdashboard-banner-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
      }

      .treasurerdashboard-btn-lime {
        min-height: 36px;
        padding: 0 16px;
        border-radius: 10px;
        border: none;
        background: var(--lime);
        color: var(--navy);
        font-size: 13px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background 0.15s;
      }

      .treasurerdashboard-btn-lime:hover {
        background: var(--lime-dim);
      }

      .treasurerdashboard-btn-ghost {
        min-height: 36px;
        padding: 0 16px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background 0.15s;
      }

      .treasurerdashboard-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.16);
      }

      /* KPI STRIP */
      .treasurerdashboard-kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        animation: fade-up 0.4s 0.22s ease both;
      }

      .treasurerdashboard-kpi-box {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--card-radius);
        padding: 16px 18px;
        border-left: 4px solid transparent;
        box-shadow: var(--card-shadow);
        transition:
          box-shadow 0.18s,
          transform 0.18s;
      }

      .treasurerdashboard-kpi-box:hover {
        box-shadow: var(--card-shadow-lg);
        transform: translateY(-2px);
      }

      .treasurerdashboard-kpi-green {
        border-left-color: var(--green);
      }
      .treasurerdashboard-kpi-blue {
        border-left-color: var(--blue);
      }
      .treasurerdashboard-kpi-amber {
        border-left-color: var(--amber);
      }
      .treasurerdashboard-kpi-navy {
        border-left-color: var(--navy);
      }

      .treasurerdashboard-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        color: var(--text-soft);
        display: block;
        margin-bottom: 6px;
      }

      .treasurerdashboard-kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--navy);
        line-height: 1;
        display: block;
      }

      .treasurerdashboard-kpi-note {
        font-size: 11px;
        color: var(--text-soft);
        margin-top: 5px;
        display: block;
      }

      /* CONTENT GRID */
      .treasurerdashboard-content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
        gap: 18px;
        align-items: start;
        animation: fade-up 0.4s 0.3s ease both;
      }

      .treasurerdashboard-left-col {
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 0;
      }
      .treasurerdashboard-right-col {
        display: flex;
        flex-direction: column;
        gap: 14px;
      }

      /* PANEL */
      .treasurerdashboard-panel {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
      }

      .treasurerdashboard-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerdashboard-panel-head h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--navy);
      }

      .treasurerdashboard-panel-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-soft);
        text-decoration: none;
        transition: color 0.14s;
      }

      .treasurerdashboard-panel-link:hover {
        color: var(--navy);
      }

      /* BAR CHART */
      .treasurerdashboard-chart-wrap {
        padding: 18px 18px 12px;
      }

      .treasurerdashboard-chart-legend {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 14px;
        flex-wrap: wrap;
      }

      .treasurerdashboard-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-soft);
      }

      .treasurerdashboard-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 3px;
        flex-shrink: 0;
      }

      .treasurerdashboard-bar-chart {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        height: 160px;
        padding: 0 4px;
        border-bottom: 2px solid var(--border-color);
        border-left: 2px solid var(--border-color);
      }

      .treasurerdashboard-bar-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        flex: 1;
      }

      .treasurerdashboard-bar {
        width: 100%;
        border-radius: 5px 5px 0 0;
        min-width: 18px;
        cursor: default;
        transition: opacity 0.15s;
      }

      .treasurerdashboard-bar:hover {
        opacity: 0.82;
      }

      .treasurerdashboard-bar-label {
        font-size: 10px;
        color: var(--text-soft);
        font-weight: 600;
        text-align: center;
        margin-top: 6px;
        white-space: nowrap;
      }

      /* TABLE */
      .treasurerdashboard-table {
        width: 100%;
        border-collapse: collapse;
      }

      .treasurerdashboard-table thead th {
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-soft);
        text-transform: uppercase;
        letter-spacing: 0.35px;
        padding: 10px 18px;
        background: rgba(5, 22, 80, 0.02);
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
      }

      .treasurerdashboard-table tbody td {
        padding: 12px 18px;
        font-size: 13px;
        color: var(--text-main);
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
      }

      .treasurerdashboard-table tbody tr:last-child td {
        border-bottom: none;
      }
      .treasurerdashboard-table tbody tr {
        transition: background 0.13s;
      }
      .treasurerdashboard-table tbody tr:hover {
        background: rgba(5, 22, 80, 0.02);
      }

      .treasurerdashboard-doc-name {
        font-weight: 600;
        color: var(--navy);
      }
      .treasurerdashboard-revenue-total {
        font-size: 13px;
        font-weight: 700;
        color: var(--green-text);
      }

      .treasurerdashboard-progress-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .treasurerdashboard-progress-bar {
        flex: 1;
        height: 6px;
        background: rgba(5, 22, 80, 0.08);
        border-radius: 999px;
        overflow: hidden;
        min-width: 60px;
      }

      .treasurerdashboard-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--navy);
        transition: width 0.3s ease;
      }

      .treasurerdashboard-progress-pct {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-soft);
        white-space: nowrap;
        min-width: 34px;
        text-align: right;
      }

      /* PILLS */
      .treasurerdashboard-pill {
        display: inline-flex;
        align-items: center;
        height: 22px;
        padding: 0 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        white-space: nowrap;
      }

      .treasurerdashboard-pill-collected {
        background: var(--green-bg);
        color: var(--green-text);
        border: 1px solid rgba(34, 197, 94, 0.2);
      }
      .treasurerdashboard-pill-pending {
        background: var(--amber-bg);
        color: var(--amber-text);
        border: 1px solid rgba(245, 158, 11, 0.22);
      }
      .treasurerdashboard-pill-waived {
        background: var(--blue-bg);
        color: var(--blue-text);
        border: 1px solid rgba(59, 130, 246, 0.2);
      }

      /* TABLE FOOTER */
      .treasurerdashboard-table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 18px;
        border-top: 1px solid var(--border-color);
        background: rgba(5, 22, 80, 0.015);
      }

      .treasurerdashboard-table-footer-note {
        font-size: 12px;
        color: var(--text-soft);
      }
      .treasurerdashboard-table-footer-total {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
      }

      /* RIGHT PANELS */
      .treasurerdashboard-breakdown-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerdashboard-breakdown-row:last-child {
        border-bottom: none;
      }
      .treasurerdashboard-breakdown-name {
        font-size: 13px;
        color: var(--text-main);
      }
      .treasurerdashboard-breakdown-amount {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
      }
      .treasurerdashboard-breakdown-waived {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-soft);
      }

      /* TRANSACTIONS */
      .treasurerdashboard-txn-row {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border-color);
        transition: background 0.13s;
      }

      .treasurerdashboard-txn-row:last-child {
        border-bottom: none;
      }
      .treasurerdashboard-txn-row:hover {
        background: rgba(5, 22, 80, 0.02);
      }

      .treasurerdashboard-txn-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      .treasurerdashboard-txn-dot-green {
        background: var(--green);
      }
      .treasurerdashboard-txn-dot-amber {
        background: var(--amber);
      }
      .treasurerdashboard-txn-dot-blue {
        background: var(--blue);
      }

      .treasurerdashboard-txn-info {
        flex: 1;
        min-width: 0;
      }

      .treasurerdashboard-txn-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--navy);
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .treasurerdashboard-txn-doc {
        font-size: 11px;
        color: var(--text-soft);
      }
      .treasurerdashboard-txn-amount {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
      }
      .treasurerdashboard-txn-pending {
        font-size: 12px;
        color: var(--amber-text);
        font-weight: 700;
      }
      .treasurerdashboard-txn-waived {
        font-size: 12px;
        color: var(--text-soft);
        font-weight: 600;
      }

      /* QUICK ACTIONS */
      .treasurerdashboard-action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 14px 16px;
      }

      .treasurerdashboard-action-btn {
        min-height: 36px;
        padding: 0 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--surface);
        color: var(--navy);
        font-size: 12px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 7px;
        transition: all 0.14s;
      }

      .treasurerdashboard-action-btn:hover {
        background: rgba(5, 22, 80, 0.04);
        border-color: var(--navy);
      }

      .treasurerdashboard-btn-outline-sm {
        min-height: 28px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--surface);
        color: var(--navy);
        font-size: 12px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.14s;
      }

      .treasurerdashboard-btn-outline-sm:hover {
        background: rgba(5, 22, 80, 0.04);
        border-color: var(--navy);
      }

      /* RESPONSIVE */
      @media (max-width: 1100px) {
        .treasurerdashboard-kpi-strip {
          grid-template-columns: repeat(2, 1fr);
        }
        .treasurerdashboard-content-grid {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 780px) {
        :root {
          --sidebar-width: 0px;
        }
        .treasurerdashboard-sidebar {
          display: none;
        }
        .treasurerdashboard-main {
          margin-left: 0;
        }
      }

      @media (max-width: 560px) {
        .treasurerdashboard-body {
          padding: 14px 14px 44px;
        }
        .treasurerdashboard-kpi-strip {
          grid-template-columns: 1fr 1fr;
        }
        .treasurerdashboard-banner {
          flex-direction: column;
          align-items: flex-start;
        }
        .treasurerdashboard-action-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>

  <body>
    <div class="treasurerdashboard-page">
      <!-- SIDEBAR -->
      <aside class="treasurerdashboard-sidebar">
        <div class="treasurerdashboard-brand-row">
          <div class="treasurerdashboard-brand-logo">
            <img src="alapan.png" alt="Alapan" />
          </div>
          <div>
            <div class="treasurerdashboard-brand-name">Alapan 1-A</div>
            <div class="treasurerdashboard-brand-sub">BarangayKonek</div>
          </div>
        </div>

        <nav class="treasurerdashboard-nav-list">
          <a
            href="treasurerdashboard.html"
            class="treasurerdashboard-nav-link active"
          >
            <span class="treasurerdashboard-nav-text">Dashboard</span>
          </a>

          <div class="treasurerdashboard-nav-divider"></div>

          <a
            href="treasurercollection.html"
            class="treasurerdashboard-nav-link"
          >
            <span class="treasurerdashboard-nav-text">Collections</span>
          </a>
          <a href="treasurerfee.html" class="treasurerdashboard-nav-link">
            <span class="treasurerdashboard-nav-text">Fee Records</span>
          </a>
          <a href="treasurerreport.html" class="treasurerdashboard-nav-link">
            <span class="treasurerdashboard-nav-text">Financial Reports</span>
          </a>

          <div class="treasurerdashboard-nav-divider"></div>

          <a href="treasurercommunity.html" class="treasurerdashboard-nav-link">
            <span class="treasurerdashboard-nav-text">Community</span>
          </a>
          <a href="#" class="treasurerdashboard-nav-link">
            <span class="treasurerdashboard-nav-text">Activity Logs</span>
          </a>
        </nav>

        <div class="treasurerdashboard-sidebar-bottom">
          <a href="home.html" class="treasurerdashboard-logout-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Logout
          </a>
        </div>
      </aside>

      <!-- MAIN -->
      <div class="treasurerdashboard-main">
        <!-- TOPBAR -->
        <header class="treasurerdashboard-topbar">
          <div class="treasurerdashboard-topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              placeholder="Search records, residents, transactions…"
            />
          </div>

          <div class="treasurerdashboard-topbar-right">
            <a href="#" class="treasurerdashboard-notif-btn">
              <i class="fa-solid fa-bell"></i>
              <span class="treasurerdashboard-notif-dot"></span>
            </a>
            <div class="treasurerdashboard-profile-chip">
              <div class="treasurerdashboard-profile-avatar">LR</div>
              <div>
                <div class="treasurerdashboard-profile-name">Luz Reyes</div>
                <div class="treasurerdashboard-profile-role">Treasurer</div>
              </div>
            </div>
          </div>
        </header>

        <!-- BODY -->
        <div class="treasurerdashboard-body">
          <!-- BANNER -->
          <div class="treasurerdashboard-banner">
            <div class="treasurerdashboard-banner-text">
              <h2>Financial Overview — April 2026</h2>
              <p>
                Track collections, fees, and revenue from document services.
              </p>
            </div>
            <div class="treasurerdashboard-banner-actions">
              <button class="treasurerdashboard-btn-lime">
                <i class="fa-solid fa-file-export"></i> Export Report
              </button>
              <button class="treasurerdashboard-btn-ghost">
                <i class="fa-solid fa-print"></i> Print
              </button>
            </div>
          </div>

          <!-- KPI STRIP -->
          <div class="treasurerdashboard-kpi-strip">
            <div
              class="treasurerdashboard-kpi-box treasurerdashboard-kpi-green"
            >
              <span class="treasurerdashboard-kpi-label">Total Collected</span>
              <span class="treasurerdashboard-kpi-value">&#8369;13,100</span>
              <span class="treasurerdashboard-kpi-note"
                >All documents this month</span
              >
            </div>
            <div class="treasurerdashboard-kpi-box treasurerdashboard-kpi-blue">
              <span class="treasurerdashboard-kpi-label">Documents Issued</span>
              <span class="treasurerdashboard-kpi-value">164</span>
              <span class="treasurerdashboard-kpi-note"
                >Released in April 2026</span
              >
            </div>
            <div
              class="treasurerdashboard-kpi-box treasurerdashboard-kpi-amber"
            >
              <span class="treasurerdashboard-kpi-label"
                >Pending Collection</span
              >
              <span class="treasurerdashboard-kpi-value">&#8369;1,200</span>
              <span class="treasurerdashboard-kpi-note"
                >8 unpaid transactions</span
              >
            </div>
            <div class="treasurerdashboard-kpi-box treasurerdashboard-kpi-navy">
              <span class="treasurerdashboard-kpi-label">Waivers Granted</span>
              <span class="treasurerdashboard-kpi-value">22</span>
              <span class="treasurerdashboard-kpi-note"
                >Indigency — no fee collected</span
              >
            </div>
          </div>

          <!-- CONTENT GRID -->
          <div class="treasurerdashboard-content-grid">
            <!-- LEFT -->
            <div class="treasurerdashboard-left-col">
              <!-- Monthly Revenue Chart -->
              <div class="treasurerdashboard-panel">
                <div class="treasurerdashboard-panel-head">
                  <h4>Monthly Revenue — 2026</h4>
                  <a href="#" class="treasurerdashboard-panel-link">View All</a>
                </div>
                <div class="treasurerdashboard-chart-wrap">
                  <div class="treasurerdashboard-chart-legend">
                    <span class="treasurerdashboard-legend-item">
                      <span
                        class="treasurerdashboard-legend-dot"
                        style="background: var(--navy)"
                      ></span>
                      Collected (&#8369;)
                    </span>
                    <span class="treasurerdashboard-legend-item">
                      <span
                        class="treasurerdashboard-legend-dot"
                        style="
                          background: rgba(5, 22, 80, 0.15);
                          border: 1px dashed #ccc;
                        "
                      ></span>
                      Projected
                    </span>
                  </div>
                  <div class="treasurerdashboard-bar-chart">
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="height: 62px; background: var(--navy)"
                      ></div>
                      <span class="treasurerdashboard-bar-label">Jan</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="height: 78px; background: var(--navy)"
                      ></div>
                      <span class="treasurerdashboard-bar-label">Feb</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="height: 70px; background: var(--navy)"
                      ></div>
                      <span class="treasurerdashboard-bar-label">Mar</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 148px;
                          background: var(--lime-dim);
                          border: 2px solid var(--navy);
                        "
                      ></div>
                      <span
                        class="treasurerdashboard-bar-label"
                        style="color: var(--navy); font-weight: 700"
                        >Apr &#9679;</span
                      >
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">May</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Jun</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Jul</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Aug</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Sep</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Oct</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Nov</span>
                    </div>
                    <div class="treasurerdashboard-bar-group">
                      <div
                        class="treasurerdashboard-bar"
                        style="
                          height: 34px;
                          background: rgba(5, 22, 80, 0.1);
                          border: 1px dashed var(--border-color);
                        "
                      ></div>
                      <span class="treasurerdashboard-bar-label">Dec</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Revenue by Document Type -->
              <div class="treasurerdashboard-panel">
                <div class="treasurerdashboard-panel-head">
                  <h4>Revenue by Document Type</h4>
                  <button class="treasurerdashboard-btn-outline-sm">
                    <i class="fa-solid fa-file-export"></i> Export
                  </button>
                </div>

                <table class="treasurerdashboard-table">
                  <thead>
                    <tr>
                      <th>Document Type</th>
                      <th>Fee</th>
                      <th>Issued</th>
                      <th>Revenue</th>
                      <th>Share</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <span class="treasurerdashboard-doc-name"
                          >Barangay Clearance</span
                        >
                      </td>
                      <td>&#8369;100</td>
                      <td>54</td>
                      <td>
                        <span class="treasurerdashboard-revenue-total"
                          >&#8369;5,400</span
                        >
                      </td>
                      <td>
                        <div class="treasurerdashboard-progress-wrap">
                          <div class="treasurerdashboard-progress-bar">
                            <div
                              class="treasurerdashboard-progress-fill"
                              style="width: 73%"
                            ></div>
                          </div>
                          <span class="treasurerdashboard-progress-pct"
                            >73%</span
                          >
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerdashboard-pill treasurerdashboard-pill-collected"
                          >Collected</span
                        >
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <span class="treasurerdashboard-doc-name"
                          >Business Permit</span
                        >
                      </td>
                      <td>&#8369;200</td>
                      <td>16</td>
                      <td>
                        <span class="treasurerdashboard-revenue-total"
                          >&#8369;3,200</span
                        >
                      </td>
                      <td>
                        <div class="treasurerdashboard-progress-wrap">
                          <div class="treasurerdashboard-progress-bar">
                            <div
                              class="treasurerdashboard-progress-fill"
                              style="width: 43%"
                            ></div>
                          </div>
                          <span class="treasurerdashboard-progress-pct"
                            >43%</span
                          >
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerdashboard-pill treasurerdashboard-pill-collected"
                          >Collected</span
                        >
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <span class="treasurerdashboard-doc-name"
                          >Certificate of Residency</span
                        >
                      </td>
                      <td>&#8369;50</td>
                      <td>38</td>
                      <td>
                        <span class="treasurerdashboard-revenue-total"
                          >&#8369;1,900</span
                        >
                      </td>
                      <td>
                        <div class="treasurerdashboard-progress-wrap">
                          <div class="treasurerdashboard-progress-bar">
                            <div
                              class="treasurerdashboard-progress-fill"
                              style="width: 51%"
                            ></div>
                          </div>
                          <span class="treasurerdashboard-progress-pct"
                            >51%</span
                          >
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerdashboard-pill treasurerdashboard-pill-collected"
                          >Collected</span
                        >
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <span class="treasurerdashboard-doc-name"
                          >Certificate of Good Moral</span
                        >
                      </td>
                      <td>&#8369;100</td>
                      <td>18</td>
                      <td>
                        <span class="treasurerdashboard-revenue-total"
                          >&#8369;1,800</span
                        >
                      </td>
                      <td>
                        <div class="treasurerdashboard-progress-wrap">
                          <div class="treasurerdashboard-progress-bar">
                            <div
                              class="treasurerdashboard-progress-fill"
                              style="width: 24%"
                            ></div>
                          </div>
                          <span class="treasurerdashboard-progress-pct"
                            >24%</span
                          >
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerdashboard-pill treasurerdashboard-pill-pending"
                          >Partial</span
                        >
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <span class="treasurerdashboard-doc-name"
                          >Barangay ID</span
                        >
                      </td>
                      <td>&#8369;50</td>
                      <td>16</td>
                      <td>
                        <span class="treasurerdashboard-revenue-total"
                          >&#8369;800</span
                        >
                      </td>
                      <td>
                        <div class="treasurerdashboard-progress-wrap">
                          <div class="treasurerdashboard-progress-bar">
                            <div
                              class="treasurerdashboard-progress-fill"
                              style="width: 11%"
                            ></div>
                          </div>
                          <span class="treasurerdashboard-progress-pct"
                            >11%</span
                          >
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerdashboard-pill treasurerdashboard-pill-collected"
                          >Collected</span
                        >
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <span class="treasurerdashboard-doc-name"
                          >Barangay Indigency</span
                        >
                      </td>
                      <td>&#8369;0</td>
                      <td>22</td>
                      <td>
                        <span style="font-size: 13px; color: var(--text-soft)"
                          >&#8212;</span
                        >
                      </td>
                      <td>
                        <div class="treasurerdashboard-progress-wrap">
                          <div class="treasurerdashboard-progress-bar">
                            <div
                              class="treasurerdashboard-progress-fill"
                              style="width: 0%; background: var(--blue)"
                            ></div>
                          </div>
                          <span class="treasurerdashboard-progress-pct"
                            >0%</span
                          >
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerdashboard-pill treasurerdashboard-pill-waived"
                          >Waived</span
                        >
                      </td>
                    </tr>
                  </tbody>
                </table>

                <div class="treasurerdashboard-table-footer">
                  <span class="treasurerdashboard-table-footer-note"
                    >6 document types &middot; 164 total issued</span
                  >
                  <span class="treasurerdashboard-table-footer-total"
                    >Total: &#8369;13,100</span
                  >
                </div>
              </div>
            </div>

            <!-- RIGHT -->
            <div class="treasurerdashboard-right-col">
              <!-- Quick Actions -->
              <div class="treasurerdashboard-panel">
                <div class="treasurerdashboard-panel-head">
                  <h4>Quick Actions</h4>
                </div>
                <div class="treasurerdashboard-action-grid">
                  <button class="treasurerdashboard-action-btn">
                    Record Payment
                  </button>
                  <button class="treasurerdashboard-action-btn">
                    Issue Receipt
                  </button>
                  <button class="treasurerdashboard-action-btn">
                    Add Expense
                  </button>
                  <button class="treasurerdashboard-action-btn">
                    Monthly Report
                  </button>
                  <button class="treasurerdashboard-action-btn">
                    Grant Waiver
                  </button>
                  <button class="treasurerdashboard-action-btn">
                    View Logs
                  </button>
                </div>
              </div>

              <!-- Collected this month -->
              <div class="treasurerdashboard-panel">
                <div class="treasurerdashboard-panel-head">
                  <h4>Collected This Month</h4>
                </div>
                <div class="treasurerdashboard-breakdown-row">
                  <span class="treasurerdashboard-breakdown-name"
                    >Barangay Clearance</span
                  >
                  <span class="treasurerdashboard-breakdown-amount"
                    >&#8369;5,400</span
                  >
                </div>
                <div class="treasurerdashboard-breakdown-row">
                  <span class="treasurerdashboard-breakdown-name"
                    >Business Permit</span
                  >
                  <span class="treasurerdashboard-breakdown-amount"
                    >&#8369;3,200</span
                  >
                </div>
                <div class="treasurerdashboard-breakdown-row">
                  <span class="treasurerdashboard-breakdown-name"
                    >Cert. of Residency</span
                  >
                  <span class="treasurerdashboard-breakdown-amount"
                    >&#8369;1,900</span
                  >
                </div>
                <div class="treasurerdashboard-breakdown-row">
                  <span class="treasurerdashboard-breakdown-name"
                    >Good Moral Cert.</span
                  >
                  <span class="treasurerdashboard-breakdown-amount"
                    >&#8369;1,800</span
                  >
                </div>
                <div class="treasurerdashboard-breakdown-row">
                  <span class="treasurerdashboard-breakdown-name"
                    >Barangay ID</span
                  >
                  <span class="treasurerdashboard-breakdown-amount"
                    >&#8369;800</span
                  >
                </div>
                <div class="treasurerdashboard-breakdown-row">
                  <span class="treasurerdashboard-breakdown-name"
                    >Barangay Indigency</span
                  >
                  <span class="treasurerdashboard-breakdown-waived"
                    >Waived</span
                  >
                </div>
              </div>

              <!-- Recent Transactions -->
              <div class="treasurerdashboard-panel">
                <div class="treasurerdashboard-panel-head">
                  <h4>Recent Transactions</h4>
                  <a href="#" class="treasurerdashboard-panel-link">View All</a>
                </div>
                <div class="treasurerdashboard-txn-row">
                  <span
                    class="treasurerdashboard-txn-dot treasurerdashboard-txn-dot-green"
                  ></span>
                  <div class="treasurerdashboard-txn-info">
                    <span class="treasurerdashboard-txn-name">Ana Lopez</span>
                    <span class="treasurerdashboard-txn-doc"
                      >Barangay Clearance</span
                    >
                  </div>
                  <span class="treasurerdashboard-txn-amount">&#8369;100</span>
                </div>
                <div class="treasurerdashboard-txn-row">
                  <span
                    class="treasurerdashboard-txn-dot treasurerdashboard-txn-dot-green"
                  ></span>
                  <div class="treasurerdashboard-txn-info">
                    <span class="treasurerdashboard-txn-name">Ryan Gomez</span>
                    <span class="treasurerdashboard-txn-doc"
                      >Certificate of Residency</span
                    >
                  </div>
                  <span class="treasurerdashboard-txn-amount">&#8369;50</span>
                </div>
                <div class="treasurerdashboard-txn-row">
                  <span
                    class="treasurerdashboard-txn-dot treasurerdashboard-txn-dot-green"
                  ></span>
                  <div class="treasurerdashboard-txn-info">
                    <span class="treasurerdashboard-txn-name"
                      >Daniel Reyes</span
                    >
                    <span class="treasurerdashboard-txn-doc"
                      >Business Permit</span
                    >
                  </div>
                  <span class="treasurerdashboard-txn-amount">&#8369;200</span>
                </div>
                <div class="treasurerdashboard-txn-row">
                  <span
                    class="treasurerdashboard-txn-dot treasurerdashboard-txn-dot-blue"
                  ></span>
                  <div class="treasurerdashboard-txn-info">
                    <span class="treasurerdashboard-txn-name">Elena Reyes</span>
                    <span class="treasurerdashboard-txn-doc"
                      >Barangay Indigency</span
                    >
                  </div>
                  <span class="treasurerdashboard-txn-waived">Waived</span>
                </div>
                <div class="treasurerdashboard-txn-row">
                  <span
                    class="treasurerdashboard-txn-dot treasurerdashboard-txn-dot-amber"
                  ></span>
                  <div class="treasurerdashboard-txn-info">
                    <span class="treasurerdashboard-txn-name"
                      >Jose Panganiban</span
                    >
                    <span class="treasurerdashboard-txn-doc"
                      >Barangay Clearance</span
                    >
                  </div>
                  <span class="treasurerdashboard-txn-pending">Pending</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
