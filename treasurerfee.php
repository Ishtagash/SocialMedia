<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fee Records — BarangayKonek</title>
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
        --red: #ef4444;
        --red-bg: #fef2f2;
        --red-text: #991b1b;
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

      /* ══════════════════════════════
         PAGE WRAPPER
      ══════════════════════════════ */

      .treasurerfeerecords-page {
        display: flex;
        min-height: 100vh;
      }

      /* ══════════════════════════════
         SIDEBAR
      ══════════════════════════════ */

      .treasurerfeerecords-sidebar {
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

      .treasurerfeerecords-brand-row {
        height: var(--topbar-height);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
      }

      .treasurerfeerecords-brand-logo {
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

      .treasurerfeerecords-brand-logo i {
        font-size: 13px;
        color: var(--navy);
      }
      .treasurerfeerecords-brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .treasurerfeerecords-brand-name {
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
      }

      .treasurerfeerecords-brand-sub {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.4);
      }

      .treasurerfeerecords-nav-list {
        flex: 1;
        padding: 14px 10px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        overflow-y: auto;
      }

      .treasurerfeerecords-nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.07);
        margin: 7px 8px;
      }

      .treasurerfeerecords-nav-link {
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

      .treasurerfeerecords-nav-link:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
      }

      .treasurerfeerecords-nav-link.active {
        background: var(--lime);
        color: var(--navy);
      }

      .treasurerfeerecords-nav-text {
        flex: 1;
      }

      .treasurerfeerecords-sidebar-bottom {
        padding: 12px 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.07);
      }

      .treasurerfeerecords-logout-link {
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

      .treasurerfeerecords-logout-link i {
        font-size: 12px;
      }

      .treasurerfeerecords-logout-link:hover {
        background: rgba(220, 38, 38, 0.12);
        color: #fca5a5;
      }

      /* ══════════════════════════════
         MAIN
      ══════════════════════════════ */

      .treasurerfeerecords-main {
        margin-left: var(--sidebar-width);
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        min-width: 0;
      }

      /* ══════════════════════════════
         TOPBAR
      ══════════════════════════════ */

      .treasurerfeerecords-topbar {
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

      .treasurerfeerecords-topbar-logo {
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

      .treasurerfeerecords-topbar-logo i {
        font-size: 12px;
        color: var(--lime);
      }
      .treasurerfeerecords-topbar-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .treasurerfeerecords-topbar-search {
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

      .treasurerfeerecords-topbar-search:focus-within {
        border-color: var(--navy);
        background: var(--surface);
      }

      .treasurerfeerecords-topbar-search i {
        color: var(--text-soft);
        font-size: 12px;
        flex-shrink: 0;
      }

      .treasurerfeerecords-topbar-search input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-family: inherit;
        font-size: 13px;
        color: var(--text-main);
      }

      .treasurerfeerecords-topbar-search input::placeholder {
        color: var(--text-soft);
      }

      .treasurerfeerecords-topbar-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .treasurerfeerecords-notif-btn {
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

      .treasurerfeerecords-notif-btn:hover {
        border-color: var(--navy);
        color: var(--navy);
      }

      .treasurerfeerecords-notif-dot {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 7px;
        height: 7px;
        background: var(--green);
        border-radius: 50%;
        border: 1.5px solid var(--surface);
      }

      .treasurerfeerecords-profile-chip {
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

      .treasurerfeerecords-profile-chip:hover {
        border-color: var(--navy);
      }

      .treasurerfeerecords-profile-avatar {
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

      .treasurerfeerecords-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .treasurerfeerecords-profile-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
      }

      .treasurerfeerecords-profile-role {
        font-size: 11px;
        color: var(--text-soft);
        line-height: 1;
      }

      /* ══════════════════════════════
         BODY
      ══════════════════════════════ */

      .treasurerfeerecords-body {
        padding: 22px 24px 48px;
        display: flex;
        flex-direction: column;
        gap: 20px;
      }

      /* ─── BANNER ─── */

      .treasurerfeerecords-banner {
        background: var(--navy);
        border-radius: var(--card-radius);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        animation: fade-up 0.4s 0.14s ease both;
      }

      .treasurerfeerecords-banner-text h2 {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
      }

      .treasurerfeerecords-banner-text p {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 4px;
      }

      .treasurerfeerecords-banner-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
      }

      .treasurerfeerecords-btn-lime {
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

      .treasurerfeerecords-btn-lime:hover {
        background: var(--lime-dim);
      }

      .treasurerfeerecords-btn-ghost {
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

      .treasurerfeerecords-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.16);
      }

      /* ─── KPI STRIP ─── */

      .treasurerfeerecords-kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        animation: fade-up 0.4s 0.22s ease both;
      }

      .treasurerfeerecords-kpi-box {
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

      .treasurerfeerecords-kpi-box:hover {
        box-shadow: var(--card-shadow-lg);
        transform: translateY(-2px);
      }

      .treasurerfeerecords-kpi-green {
        border-left-color: var(--green);
      }
      .treasurerfeerecords-kpi-blue {
        border-left-color: var(--blue);
      }
      .treasurerfeerecords-kpi-amber {
        border-left-color: var(--amber);
      }
      .treasurerfeerecords-kpi-navy {
        border-left-color: var(--navy);
      }

      .treasurerfeerecords-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        color: var(--text-soft);
        display: block;
        margin-bottom: 6px;
      }

      .treasurerfeerecords-kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--navy);
        line-height: 1;
        display: block;
      }

      .treasurerfeerecords-kpi-note {
        font-size: 11px;
        color: var(--text-soft);
        margin-top: 5px;
        display: block;
      }

      /* ─── CONTENT GRID ─── */

      .treasurerfeerecords-content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 18px;
        align-items: start;
        animation: fade-up 0.4s 0.3s ease both;
      }

      .treasurerfeerecords-left {
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 0;
      }

      .treasurerfeerecords-right {
        display: flex;
        flex-direction: column;
        gap: 14px;
      }

      /* ─── FEE SCHEDULE CONTAINER ─── */

      .treasurerfeerecords-schedule-container {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
      }

      .treasurerfeerecords-schedule-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerfeerecords-schedule-head h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--navy);
      }

      .treasurerfeerecords-schedule-head-right {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .treasurerfeerecords-last-updated {
        font-size: 11px;
        color: var(--text-soft);
      }

      /* fee table */

      .treasurerfeerecords-fee-table {
        width: 100%;
        border-collapse: collapse;
      }

      .treasurerfeerecords-fee-table thead th {
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

      .treasurerfeerecords-fee-table tbody td {
        padding: 14px 18px;
        font-size: 13px;
        color: var(--text-main);
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
      }

      .treasurerfeerecords-fee-table tbody tr:last-child td {
        border-bottom: none;
      }

      .treasurerfeerecords-fee-table tbody tr {
        transition: background 0.13s;
      }

      .treasurerfeerecords-fee-table tbody tr:hover {
        background: rgba(5, 22, 80, 0.02);
      }

      /* document icon + name cell */

      .treasurerfeerecords-doc-cell {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .treasurerfeerecords-doc-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: rgba(5, 22, 80, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: var(--navy);
        flex-shrink: 0;
      }

      .treasurerfeerecords-doc-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        display: block;
      }

      .treasurerfeerecords-doc-desc {
        font-size: 11px;
        color: var(--text-soft);
      }

      /* fee display + editable input */

      .treasurerfeerecords-fee-display {
        font-size: 15px;
        font-weight: 700;
        color: var(--navy);
      }

      .treasurerfeerecords-fee-free {
        font-size: 13px;
        font-weight: 600;
        color: var(--blue-text);
      }

      .treasurerfeerecords-fee-input {
        width: 90px;
        height: 32px;
        padding: 0 10px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--page-bg);
        font-size: 13px;
        font-weight: 700;
        font-family: inherit;
        color: var(--navy);
        outline: none;
        display: none;
        transition: border-color 0.15s;
      }

      .treasurerfeerecords-fee-input:focus {
        border-color: var(--navy);
        background: var(--surface);
      }

      .treasurerfeerecords-fee-input.visible {
        display: block;
      }

      /* usage bar */

      .treasurerfeerecords-usage-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .treasurerfeerecords-usage-bar {
        flex: 1;
        height: 7px;
        background: rgba(5, 22, 80, 0.07);
        border-radius: 999px;
        overflow: hidden;
        min-width: 70px;
      }

      .treasurerfeerecords-usage-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--navy);
      }

      .treasurerfeerecords-usage-count {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-soft);
        min-width: 28px;
        text-align: right;
      }

      /* revenue cell */

      .treasurerfeerecords-row-revenue {
        font-size: 13px;
        font-weight: 700;
        color: var(--green-text);
      }

      .treasurerfeerecords-row-revenue-zero {
        font-size: 13px;
        color: var(--text-soft);
      }

      /* status pill */

      .treasurerfeerecords-pill {
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

      .treasurerfeerecords-pill-active {
        background: var(--green-bg);
        color: var(--green-text);
        border: 1px solid rgba(34, 197, 94, 0.2);
      }
      .treasurerfeerecords-pill-waived {
        background: var(--blue-bg);
        color: var(--blue-text);
        border: 1px solid rgba(59, 130, 246, 0.2);
      }
      .treasurerfeerecords-pill-inactive {
        background: rgba(5, 22, 80, 0.05);
        color: var(--text-soft);
        border: 1px solid var(--border-color);
      }

      /* action buttons */

      .treasurerfeerecords-action-cell {
        display: flex;
        gap: 5px;
      }

      .treasurerfeerecords-edit-btn {
        min-height: 28px;
        padding: 0 11px;
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
        gap: 4px;
        transition: all 0.14s;
      }

      .treasurerfeerecords-edit-btn:hover {
        background: rgba(5, 22, 80, 0.04);
        border-color: var(--navy);
      }

      .treasurerfeerecords-save-btn {
        min-height: 28px;
        padding: 0 11px;
        border-radius: 8px;
        border: none;
        background: var(--navy);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: none;
        align-items: center;
        gap: 4px;
        transition: background 0.14s;
      }

      .treasurerfeerecords-save-btn:hover {
        background: var(--navy-mid);
      }
      .treasurerfeerecords-save-btn.visible {
        display: inline-flex;
      }

      /* schedule footer total */

      .treasurerfeerecords-schedule-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 18px;
        border-top: 1px solid var(--border-color);
        background: rgba(5, 22, 80, 0.015);
      }

      .treasurerfeerecords-footer-note {
        font-size: 12px;
        color: var(--text-soft);
      }

      .treasurerfeerecords-footer-total {
        font-size: 14px;
        font-weight: 700;
        color: var(--green-text);
      }

      /* ─── CHANGE LOG ─── */

      .treasurerfeerecords-log-container {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
      }

      .treasurerfeerecords-log-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerfeerecords-log-head h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--navy);
      }

      .treasurerfeerecords-log-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-soft);
        text-decoration: none;
        transition: color 0.14s;
      }

      .treasurerfeerecords-log-link:hover {
        color: var(--navy);
      }

      .treasurerfeerecords-log-row {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 13px 18px;
        border-bottom: 1px solid var(--border-color);
        transition: background 0.13s;
      }

      .treasurerfeerecords-log-row:last-child {
        border-bottom: none;
      }
      .treasurerfeerecords-log-row:hover {
        background: rgba(5, 22, 80, 0.02);
      }

      .treasurerfeerecords-log-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--navy);
        flex-shrink: 0;
        margin-top: 4px;
        opacity: 0.4;
      }

      .treasurerfeerecords-log-dot-change {
        background: var(--amber);
        opacity: 1;
      }
      .treasurerfeerecords-log-dot-new {
        background: var(--green);
        opacity: 1;
      }

      .treasurerfeerecords-log-info {
        flex: 1;
        min-width: 0;
      }

      .treasurerfeerecords-log-action {
        font-size: 13px;
        font-weight: 600;
        color: var(--navy);
        display: block;
        margin-bottom: 2px;
      }

      .treasurerfeerecords-log-detail {
        font-size: 12px;
        color: var(--text-soft);
        line-height: 1.4;
      }

      .treasurerfeerecords-log-time {
        font-size: 11px;
        color: var(--text-soft);
        white-space: nowrap;
        flex-shrink: 0;
      }

      /* ══════════════════════════════
         RIGHT PANELS
      ══════════════════════════════ */

      .treasurerfeerecords-panel {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
      }

      .treasurerfeerecords-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 13px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerfeerecords-panel-head h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--navy);
      }

      .treasurerfeerecords-panel-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-soft);
        text-decoration: none;
        transition: color 0.14s;
      }

      .treasurerfeerecords-panel-link:hover {
        color: var(--navy);
      }

      /* revenue per fee panel rows */

      .treasurerfeerecords-rev-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerfeerecords-rev-row:last-child {
        border-bottom: none;
      }

      .treasurerfeerecords-rev-doc {
        font-size: 12px;
        color: var(--text-main);
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .treasurerfeerecords-rev-amount {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
      }

      .treasurerfeerecords-rev-waived {
        font-size: 12px;
        color: var(--text-soft);
        font-weight: 600;
      }

      /* mini chart — bar per doc */

      .treasurerfeerecords-mini-chart {
        padding: 14px 18px 16px;
      }

      .treasurerfeerecords-mini-row {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 10px;
      }

      .treasurerfeerecords-mini-row:last-child {
        margin-bottom: 0;
      }

      .treasurerfeerecords-mini-label {
        font-size: 12px;
        color: var(--text-soft);
        width: 88px;
        flex-shrink: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .treasurerfeerecords-mini-track {
        flex: 1;
        height: 8px;
        background: rgba(5, 22, 80, 0.07);
        border-radius: 999px;
        overflow: hidden;
      }

      .treasurerfeerecords-mini-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--navy);
      }

      .treasurerfeerecords-mini-pct {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-soft);
        width: 30px;
        text-align: right;
        flex-shrink: 0;
      }

      /* fee tier summary */

      .treasurerfeerecords-tier-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border-color);
      }

      .treasurerfeerecords-tier-row:last-child {
        border-bottom: none;
      }

      .treasurerfeerecords-tier-name {
        font-size: 13px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .treasurerfeerecords-tier-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      .treasurerfeerecords-tier-value {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
      }

      /* ─── RESPONSIVE ─── */

      @media (max-width: 1100px) {
        .treasurerfeerecords-kpi-strip {
          grid-template-columns: repeat(2, 1fr);
        }
        .treasurerfeerecords-content-grid {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 780px) {
        :root {
          --sidebar-width: 0px;
        }
        .treasurerfeerecords-sidebar {
          display: none;
        }
        .treasurerfeerecords-main {
          margin-left: 0;
        }
      }

      @media (max-width: 560px) {
        .treasurerfeerecords-body {
          padding: 14px 14px 44px;
        }
        .treasurerfeerecords-kpi-strip {
          grid-template-columns: 1fr 1fr;
        }
        .treasurerfeerecords-banner {
          flex-direction: column;
          align-items: flex-start;
        }
      }
    </style>
  </head>
  <body>
    <div class="treasurerfeerecords-page">
      <!-- SIDEBAR -->
      <aside class="treasurerfeerecords-sidebar">
        <div class="treasurerfeerecords-brand-row">
          <div class="treasurerfeerecords-brand-logo">
            <img src="alapan.png" alt="Alapan" />
          </div>
          <div>
            <div class="treasurerfeerecords-brand-name">Alapan 1-A</div>
            <div class="treasurerfeerecords-brand-sub">BarangayKonek</div>
          </div>
        </div>

        <nav class="treasurerfeerecords-nav-list">
          <a
            href="treasurerdashboard.html"
            class="treasurerfeerecords-nav-link"
          >
            <span class="treasurerfeerecords-nav-text">Dashboard</span>
          </a>
          <div class="treasurerfeerecords-nav-divider"></div>
          <a
            href="treasurercollection.html"
            class="treasurerfeerecords-nav-link"
          >
            <span class="treasurerfeerecords-nav-text">Collections</span>
          </a>
          <a
            href="treasurerfee.html"
            class="treasurerfeerecords-nav-link active"
          >
            <span class="treasurerfeerecords-nav-text">Fee Records</span>
          </a>
          <a href="treasurerreport.html" class="treasurerfeerecords-nav-link">
            <span class="treasurerfeerecords-nav-text">Financial Reports</span>
          </a>
          <div class="treasurerfeerecords-nav-divider"></div>
          <a
            href="treasurercommunity.html"
            class="treasurerfeerecords-nav-link"
          >
            <span class="treasurerfeerecords-nav-text">Community</span>
          </a>
          <a href="#" class="treasurerfeerecords-nav-link">
            <span class="treasurerfeerecords-nav-text">Activity Logs</span>
          </a>
        </nav>

        <div class="treasurerfeerecords-sidebar-bottom">
          <a href="home.html" class="treasurerfeerecords-logout-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Logout
          </a>
        </div>
      </aside>

      <!-- MAIN -->
      <div class="treasurerfeerecords-main">
        <!-- TOPBAR -->
        <header class="treasurerfeerecords-topbar">
          <div class="treasurerfeerecords-topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              placeholder="Search records, residents, transactions&#x2026;"
            />
          </div>

          <div class="treasurerfeerecords-topbar-right">
            <a href="#" class="treasurerfeerecords-notif-btn">
              <i class="fa-solid fa-bell"></i>
              <span class="treasurerfeerecords-notif-dot"></span>
            </a>
            <div class="treasurerfeerecords-profile-chip">
              <div class="treasurerfeerecords-profile-avatar">LR</div>
              <div>
                <div class="treasurerfeerecords-profile-name">Luz Reyes</div>
                <div class="treasurerfeerecords-profile-role">Treasurer</div>
              </div>
            </div>
          </div>
        </header>

        <!-- BODY -->
        <div class="treasurerfeerecords-body">
          <!-- BANNER -->
          <div class="treasurerfeerecords-banner">
            <div class="treasurerfeerecords-banner-text">
              <h2>Fee Records</h2>
              <p>
                Manage document fee rates and track revenue generated per
                service.
              </p>
            </div>
            <div class="treasurerfeerecords-banner-actions">
              <button class="treasurerfeerecords-btn-lime">
                <i class="fa-solid fa-plus"></i>
                Add Fee Type
              </button>
              <button class="treasurerfeerecords-btn-ghost">
                <i class="fa-solid fa-file-export"></i>
                Export
              </button>
            </div>
          </div>

          <!-- KPI STRIP -->
          <div class="treasurerfeerecords-kpi-strip">
            <div
              class="treasurerfeerecords-kpi-box treasurerfeerecords-kpi-green"
            >
              <span class="treasurerfeerecords-kpi-label"
                >Active Fee Types</span
              >
              <span class="treasurerfeerecords-kpi-value">5</span>
              <span class="treasurerfeerecords-kpi-note"
                >1 waived (Indigency)</span
              >
            </div>

            <div
              class="treasurerfeerecords-kpi-box treasurerfeerecords-kpi-blue"
            >
              <span class="treasurerfeerecords-kpi-label"
                >Avg. Fee per Doc</span
              >
              <span class="treasurerfeerecords-kpi-value">&#8369;100</span>
              <span class="treasurerfeerecords-kpi-note"
                >Across all paid types</span
              >
            </div>

            <div
              class="treasurerfeerecords-kpi-box treasurerfeerecords-kpi-amber"
            >
              <span class="treasurerfeerecords-kpi-label"
                >Revenue This Month</span
              >
              <span class="treasurerfeerecords-kpi-value">&#8369;13,100</span>
              <span class="treasurerfeerecords-kpi-note"
                >From document fees</span
              >
            </div>

            <div
              class="treasurerfeerecords-kpi-box treasurerfeerecords-kpi-navy"
            >
              <span class="treasurerfeerecords-kpi-label">Last Fee Update</span>
              <span class="treasurerfeerecords-kpi-value">Jan 2026</span>
              <span class="treasurerfeerecords-kpi-note">No changes since</span>
            </div>
          </div>

          <!-- CONTENT GRID -->
          <div class="treasurerfeerecords-content-grid">
            <!-- LEFT COLUMN -->
            <div class="treasurerfeerecords-left">
              <!-- Fee Schedule Table -->
              <div class="treasurerfeerecords-schedule-container">
                <div class="treasurerfeerecords-schedule-head">
                  <h4>Document Fee Schedule</h4>
                  <div class="treasurerfeerecords-schedule-head-right">
                    <span class="treasurerfeerecords-last-updated"
                      >Last updated: Jan 6, 2026</span
                    >
                  </div>
                </div>

                <table class="treasurerfeerecords-fee-table">
                  <thead>
                    <tr>
                      <th>Document Type</th>
                      <th>Fee Amount</th>
                      <th>Times Used</th>
                      <th>Revenue</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr id="fee-row-0">
                      <td>
                        <div class="treasurerfeerecords-doc-cell">
                          <div class="treasurerfeerecords-doc-icon">
                            <i class="fa-regular fa-file"></i>
                          </div>
                          <div>
                            <span class="treasurerfeerecords-doc-name"
                              >Barangay Clearance</span
                            >
                            <span class="treasurerfeerecords-doc-desc"
                              >Employment, travel, legal</span
                            >
                          </div>
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-fee-display"
                          id="fee-display-0"
                          >&#8369;100</span
                        >
                        <input
                          class="treasurerfeerecords-fee-input"
                          id="fee-input-0"
                          type="number"
                          value="100"
                          min="0"
                        />
                      </td>
                      <td>
                        <div class="treasurerfeerecords-usage-wrap">
                          <div class="treasurerfeerecords-usage-bar">
                            <div
                              class="treasurerfeerecords-usage-fill"
                              style="width: 100%"
                            ></div>
                          </div>
                          <span class="treasurerfeerecords-usage-count"
                            >54</span
                          >
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-row-revenue"
                          >&#8369;5,400</span
                        >
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-pill treasurerfeerecords-pill-active"
                          >Active</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-action-cell">
                          <button
                            class="treasurerfeerecords-edit-btn"
                            onclick="startEdit(0)"
                          >
                            Edit
                          </button>
                          <button
                            class="treasurerfeerecords-save-btn"
                            id="save-0"
                            onclick="saveEdit(0)"
                          >
                            Save
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr id="fee-row-1">
                      <td>
                        <div class="treasurerfeerecords-doc-cell">
                          <div class="treasurerfeerecords-doc-icon">
                            <i class="fa-regular fa-file"></i>
                          </div>
                          <div>
                            <span class="treasurerfeerecords-doc-name"
                              >Business Permit</span
                            >
                            <span class="treasurerfeerecords-doc-desc"
                              >Small business operations</span
                            >
                          </div>
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-fee-display"
                          id="fee-display-1"
                          >&#8369;200</span
                        >
                        <input
                          class="treasurerfeerecords-fee-input"
                          id="fee-input-1"
                          type="number"
                          value="200"
                          min="0"
                        />
                      </td>
                      <td>
                        <div class="treasurerfeerecords-usage-wrap">
                          <div class="treasurerfeerecords-usage-bar">
                            <div
                              class="treasurerfeerecords-usage-fill"
                              style="width: 30%"
                            ></div>
                          </div>
                          <span class="treasurerfeerecords-usage-count"
                            >16</span
                          >
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-row-revenue"
                          >&#8369;3,200</span
                        >
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-pill treasurerfeerecords-pill-active"
                          >Active</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-action-cell">
                          <button
                            class="treasurerfeerecords-edit-btn"
                            onclick="startEdit(1)"
                          >
                            Edit
                          </button>
                          <button
                            class="treasurerfeerecords-save-btn"
                            id="save-1"
                            onclick="saveEdit(1)"
                          >
                            Save
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr id="fee-row-2">
                      <td>
                        <div class="treasurerfeerecords-doc-cell">
                          <div class="treasurerfeerecords-doc-icon">
                            <i class="fa-regular fa-file"></i>
                          </div>
                          <div>
                            <span class="treasurerfeerecords-doc-name"
                              >Certificate of Residency</span
                            >
                            <span class="treasurerfeerecords-doc-desc"
                              >Bank, school, government use</span
                            >
                          </div>
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-fee-display"
                          id="fee-display-2"
                          >&#8369;50</span
                        >
                        <input
                          class="treasurerfeerecords-fee-input"
                          id="fee-input-2"
                          type="number"
                          value="50"
                          min="0"
                        />
                      </td>
                      <td>
                        <div class="treasurerfeerecords-usage-wrap">
                          <div class="treasurerfeerecords-usage-bar">
                            <div
                              class="treasurerfeerecords-usage-fill"
                              style="width: 70%"
                            ></div>
                          </div>
                          <span class="treasurerfeerecords-usage-count"
                            >38</span
                          >
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-row-revenue"
                          >&#8369;1,900</span
                        >
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-pill treasurerfeerecords-pill-active"
                          >Active</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-action-cell">
                          <button
                            class="treasurerfeerecords-edit-btn"
                            onclick="startEdit(2)"
                          >
                            Edit
                          </button>
                          <button
                            class="treasurerfeerecords-save-btn"
                            id="save-2"
                            onclick="saveEdit(2)"
                          >
                            Save
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr id="fee-row-3">
                      <td>
                        <div class="treasurerfeerecords-doc-cell">
                          <div class="treasurerfeerecords-doc-icon">
                            <i class="fa-regular fa-file"></i>
                          </div>
                          <div>
                            <span class="treasurerfeerecords-doc-name"
                              >Certificate of Good Moral</span
                            >
                            <span class="treasurerfeerecords-doc-desc"
                              >College, job applications</span
                            >
                          </div>
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-fee-display"
                          id="fee-display-3"
                          >&#8369;100</span
                        >
                        <input
                          class="treasurerfeerecords-fee-input"
                          id="fee-input-3"
                          type="number"
                          value="100"
                          min="0"
                        />
                      </td>
                      <td>
                        <div class="treasurerfeerecords-usage-wrap">
                          <div class="treasurerfeerecords-usage-bar">
                            <div
                              class="treasurerfeerecords-usage-fill"
                              style="width: 33%"
                            ></div>
                          </div>
                          <span class="treasurerfeerecords-usage-count"
                            >18</span
                          >
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-row-revenue"
                          >&#8369;1,800</span
                        >
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-pill treasurerfeerecords-pill-active"
                          >Active</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-action-cell">
                          <button
                            class="treasurerfeerecords-edit-btn"
                            onclick="startEdit(3)"
                          >
                            Edit
                          </button>
                          <button
                            class="treasurerfeerecords-save-btn"
                            id="save-3"
                            onclick="saveEdit(3)"
                          >
                            Save
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr id="fee-row-4">
                      <td>
                        <div class="treasurerfeerecords-doc-cell">
                          <div class="treasurerfeerecords-doc-icon">
                            <i class="fa-regular fa-id-card"></i>
                          </div>
                          <div>
                            <span class="treasurerfeerecords-doc-name"
                              >Barangay ID</span
                            >
                            <span class="treasurerfeerecords-doc-desc"
                              >Identification card issuance</span
                            >
                          </div>
                        </div>
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-fee-display"
                          id="fee-display-4"
                          >&#8369;50</span
                        >
                        <input
                          class="treasurerfeerecords-fee-input"
                          id="fee-input-4"
                          type="number"
                          value="50"
                          min="0"
                        />
                      </td>
                      <td>
                        <div class="treasurerfeerecords-usage-wrap">
                          <div class="treasurerfeerecords-usage-bar">
                            <div
                              class="treasurerfeerecords-usage-fill"
                              style="width: 30%"
                            ></div>
                          </div>
                          <span class="treasurerfeerecords-usage-count"
                            >16</span
                          >
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-row-revenue"
                          >&#8369;800</span
                        >
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-pill treasurerfeerecords-pill-active"
                          >Active</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-action-cell">
                          <button
                            class="treasurerfeerecords-edit-btn"
                            onclick="startEdit(4)"
                          >
                            Edit
                          </button>
                          <button
                            class="treasurerfeerecords-save-btn"
                            id="save-4"
                            onclick="saveEdit(4)"
                          >
                            Save
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr id="fee-row-5">
                      <td>
                        <div class="treasurerfeerecords-doc-cell">
                          <div class="treasurerfeerecords-doc-icon">
                            <i class="fa-regular fa-file"></i>
                          </div>
                          <div>
                            <span class="treasurerfeerecords-doc-name"
                              >Barangay Indigency</span
                            >
                            <span class="treasurerfeerecords-doc-desc"
                              >Government assistance support</span
                            >
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-fee-free"
                          >Free (Waived)</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-usage-wrap">
                          <div class="treasurerfeerecords-usage-bar">
                            <div
                              class="treasurerfeerecords-usage-fill"
                              style="width: 41%; background: var(--blue)"
                            ></div>
                          </div>
                          <span class="treasurerfeerecords-usage-count"
                            >22</span
                          >
                        </div>
                      </td>
                      <td>
                        <span class="treasurerfeerecords-row-revenue-zero"
                          >&#x2014;</span
                        >
                      </td>
                      <td>
                        <span
                          class="treasurerfeerecords-pill treasurerfeerecords-pill-waived"
                          >Waived</span
                        >
                      </td>
                      <td>
                        <div class="treasurerfeerecords-action-cell">
                          <button class="treasurerfeerecords-edit-btn">
                            View Policy
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <div class="treasurerfeerecords-schedule-footer">
                  <span class="treasurerfeerecords-footer-note"
                    >6 document types &middot; 164 total issued this month</span
                  >
                  <span class="treasurerfeerecords-footer-total"
                    >Month Total: &#8369;13,100</span
                  >
                </div>
              </div>

              <!-- Fee Change Log -->
              <div class="treasurerfeerecords-log-container">
                <div class="treasurerfeerecords-log-head">
                  <h4>Fee Change History</h4>
                  <a href="#" class="treasurerfeerecords-log-link">View All</a>
                </div>

                <div class="treasurerfeerecords-log-row">
                  <div class="treasurerfeerecords-log-dot"></div>
                  <div class="treasurerfeerecords-log-info">
                    <span class="treasurerfeerecords-log-action"
                      >No changes recorded this month</span
                    >
                    <span class="treasurerfeerecords-log-detail"
                      >All fees remain at rates set on January 6, 2026.</span
                    >
                  </div>
                  <span class="treasurerfeerecords-log-time">Apr 2026</span>
                </div>

                <div class="treasurerfeerecords-log-row">
                  <div
                    class="treasurerfeerecords-log-dot treasurerfeerecords-log-dot-change"
                  ></div>
                  <div class="treasurerfeerecords-log-info">
                    <span class="treasurerfeerecords-log-action"
                      >Business Permit fee updated</span
                    >
                    <span class="treasurerfeerecords-log-detail"
                      >Changed from &#8369;150 to &#8369;200 &mdash; approved by
                      Barangay Captain.</span
                    >
                  </div>
                  <span class="treasurerfeerecords-log-time">Jan 6, 2026</span>
                </div>

                <div class="treasurerfeerecords-log-row">
                  <div
                    class="treasurerfeerecords-log-dot treasurerfeerecords-log-dot-change"
                  ></div>
                  <div class="treasurerfeerecords-log-info">
                    <span class="treasurerfeerecords-log-action"
                      >Barangay ID fee introduced</span
                    >
                    <span class="treasurerfeerecords-log-detail"
                      >New fee set at &#8369;50 for ID issuance requests.</span
                    >
                  </div>
                  <span class="treasurerfeerecords-log-time">Jan 6, 2026</span>
                </div>

                <div class="treasurerfeerecords-log-row">
                  <div
                    class="treasurerfeerecords-log-dot treasurerfeerecords-log-dot-change"
                  ></div>
                  <div class="treasurerfeerecords-log-info">
                    <span class="treasurerfeerecords-log-action"
                      >Barangay Clearance fee adjusted</span
                    >
                    <span class="treasurerfeerecords-log-detail"
                      >Reduced from &#8369;150 to &#8369;100 per barangay
                      resolution.</span
                    >
                  </div>
                  <span class="treasurerfeerecords-log-time">Jul 14, 2025</span>
                </div>

                <div class="treasurerfeerecords-log-row">
                  <div
                    class="treasurerfeerecords-log-dot treasurerfeerecords-log-dot-new"
                  ></div>
                  <div class="treasurerfeerecords-log-info">
                    <span class="treasurerfeerecords-log-action"
                      >Initial fee schedule created</span
                    >
                    <span class="treasurerfeerecords-log-detail"
                      >All document types and rates set during system
                      setup.</span
                    >
                  </div>
                  <span class="treasurerfeerecords-log-time">Jan 2025</span>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="treasurerfeerecords-right">
              <!-- Revenue per fee type -->
              <div class="treasurerfeerecords-panel">
                <div class="treasurerfeerecords-panel-head">
                  <h4>Revenue per Type</h4>
                  <a
                    href="treasurer-collections.html"
                    class="treasurerfeerecords-panel-link"
                    >Collections</a
                  >
                </div>
                <div class="treasurerfeerecords-rev-row">
                  <span class="treasurerfeerecords-rev-doc"
                    >Barangay Clearance</span
                  >
                  <span class="treasurerfeerecords-rev-amount"
                    >&#8369;5,400</span
                  >
                </div>
                <div class="treasurerfeerecords-rev-row">
                  <span class="treasurerfeerecords-rev-doc"
                    >Business Permit</span
                  >
                  <span class="treasurerfeerecords-rev-amount"
                    >&#8369;3,200</span
                  >
                </div>
                <div class="treasurerfeerecords-rev-row">
                  <span class="treasurerfeerecords-rev-doc"
                    >Cert. of Residency</span
                  >
                  <span class="treasurerfeerecords-rev-amount"
                    >&#8369;1,900</span
                  >
                </div>
                <div class="treasurerfeerecords-rev-row">
                  <span class="treasurerfeerecords-rev-doc"
                    >Good Moral Cert.</span
                  >
                  <span class="treasurerfeerecords-rev-amount"
                    >&#8369;1,800</span
                  >
                </div>
                <div class="treasurerfeerecords-rev-row">
                  <span class="treasurerfeerecords-rev-doc">Barangay ID</span>
                  <span class="treasurerfeerecords-rev-amount">&#8369;800</span>
                </div>
                <div class="treasurerfeerecords-rev-row">
                  <span class="treasurerfeerecords-rev-doc"
                    >Barangay Indigency</span
                  >
                  <span class="treasurerfeerecords-rev-waived">Waived</span>
                </div>
              </div>

              <!-- Usage frequency chart -->
              <div class="treasurerfeerecords-panel">
                <div class="treasurerfeerecords-panel-head">
                  <h4>Usage Frequency</h4>
                </div>
                <div class="treasurerfeerecords-mini-chart">
                  <div class="treasurerfeerecords-mini-row">
                    <span class="treasurerfeerecords-mini-label"
                      >Brgy. Clearance</span
                    >
                    <div class="treasurerfeerecords-mini-track">
                      <div
                        class="treasurerfeerecords-mini-fill"
                        style="width: 100%"
                      ></div>
                    </div>
                    <span class="treasurerfeerecords-mini-pct">54</span>
                  </div>
                  <div class="treasurerfeerecords-mini-row">
                    <span class="treasurerfeerecords-mini-label"
                      >Cert. Residency</span
                    >
                    <div class="treasurerfeerecords-mini-track">
                      <div
                        class="treasurerfeerecords-mini-fill"
                        style="width: 70%"
                      ></div>
                    </div>
                    <span class="treasurerfeerecords-mini-pct">38</span>
                  </div>
                  <div class="treasurerfeerecords-mini-row">
                    <span class="treasurerfeerecords-mini-label"
                      >Indigency</span
                    >
                    <div class="treasurerfeerecords-mini-track">
                      <div
                        class="treasurerfeerecords-mini-fill"
                        style="width: 41%; background: var(--blue)"
                      ></div>
                    </div>
                    <span class="treasurerfeerecords-mini-pct">22</span>
                  </div>
                  <div class="treasurerfeerecords-mini-row">
                    <span class="treasurerfeerecords-mini-label"
                      >Good Moral</span
                    >
                    <div class="treasurerfeerecords-mini-track">
                      <div
                        class="treasurerfeerecords-mini-fill"
                        style="width: 33%"
                      ></div>
                    </div>
                    <span class="treasurerfeerecords-mini-pct">18</span>
                  </div>
                  <div class="treasurerfeerecords-mini-row">
                    <span class="treasurerfeerecords-mini-label"
                      >Business Permit</span
                    >
                    <div class="treasurerfeerecords-mini-track">
                      <div
                        class="treasurerfeerecords-mini-fill"
                        style="width: 30%"
                      ></div>
                    </div>
                    <span class="treasurerfeerecords-mini-pct">16</span>
                  </div>
                  <div class="treasurerfeerecords-mini-row">
                    <span class="treasurerfeerecords-mini-label"
                      >Barangay ID</span
                    >
                    <div class="treasurerfeerecords-mini-track">
                      <div
                        class="treasurerfeerecords-mini-fill"
                        style="width: 30%"
                      ></div>
                    </div>
                    <span class="treasurerfeerecords-mini-pct">16</span>
                  </div>
                </div>
              </div>

              <!-- Fee tier summary -->
              <div class="treasurerfeerecords-panel">
                <div class="treasurerfeerecords-panel-head">
                  <h4>Fee Tiers</h4>
                </div>
                <div class="treasurerfeerecords-tier-row">
                  <span class="treasurerfeerecords-tier-name">
                    <span
                      class="treasurerfeerecords-tier-dot"
                      style="background: var(--navy)"
                    ></span>
                    &#8369;200 tier
                  </span>
                  <span class="treasurerfeerecords-tier-value"
                    >Business Permit</span
                  >
                </div>
                <div class="treasurerfeerecords-tier-row">
                  <span class="treasurerfeerecords-tier-name">
                    <span
                      class="treasurerfeerecords-tier-dot"
                      style="background: var(--green)"
                    ></span>
                    &#8369;100 tier
                  </span>
                  <span class="treasurerfeerecords-tier-value"
                    >Clearance, Good Moral</span
                  >
                </div>
                <div class="treasurerfeerecords-tier-row">
                  <span class="treasurerfeerecords-tier-name">
                    <span
                      class="treasurerfeerecords-tier-dot"
                      style="background: var(--amber)"
                    ></span>
                    &#8369;50 tier
                  </span>
                  <span class="treasurerfeerecords-tier-value"
                    >Residency, ID</span
                  >
                </div>
                <div class="treasurerfeerecords-tier-row">
                  <span class="treasurerfeerecords-tier-name">
                    <span
                      class="treasurerfeerecords-tier-dot"
                      style="background: var(--blue)"
                    ></span>
                    Free (Waived)
                  </span>
                  <span class="treasurerfeerecords-tier-value">Indigency</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      /* inline fee edit toggle */
      function startEdit(index) {
        var display = document.getElementById("fee-display-" + index);
        var input = document.getElementById("fee-input-" + index);
        var saveBtn = document.getElementById("save-" + index);
        if (!display || !input || !saveBtn) return;
        display.style.display = "none";
        input.classList.add("visible");
        saveBtn.classList.add("visible");
        input.focus();
      }

      function saveEdit(index) {
        var display = document.getElementById("fee-display-" + index);
        var input = document.getElementById("fee-input-" + index);
        var saveBtn = document.getElementById("save-" + index);
        if (!display || !input || !saveBtn) return;
        var newVal = parseInt(input.value, 10);
        if (!isNaN(newVal) && newVal >= 0) {
          display.textContent = "\u20B1" + newVal.toLocaleString();
        }
        input.classList.remove("visible");
        saveBtn.classList.remove("visible");
        display.style.display = "";
      }
    </script>
  </body>
</html>
