<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Treasurer Dashboard — BarangayKonek</title>

    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />

    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    />

    <style>
      :root {
        --navy: #051650;
        --navy-mid: #0a2160;
        --lime: #ccff00;
        --surface: #ffffff;
        --bg: #f4f7fb;
        --soft-bg: #f8fafc;
        --border: #e3e7f0;
        --text: #1a2240;
        --text-muted: #7a869a;
        --sidebar-w: 228px;
        --sidebar-mini: 64px;
        --topbar-h: 62px;
        --soft-navy-bg: #edf2ff;
        --shadow-light: 0 6px 18px rgba(5, 22, 80, 0.08);
        --shadow-hover: 0 14px 30px rgba(5, 22, 80, 0.13);
        --shadow-sidebar: 6px 0 18px rgba(5, 22, 80, 0.14);
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        font-family: "DM Sans", sans-serif;
        background:
          radial-gradient(
            circle at top left,
            rgba(204, 255, 0, 0.1),
            transparent 32%
          ),
          linear-gradient(135deg, #f8fbff 0%, #eef3fb 100%);
        color: var(--text);
        min-height: 100vh;
        overflow-x: hidden;
      }

      a {
        text-decoration: none;
      }

      button,
      input,
      select {
        font-family: inherit;
      }

      @keyframes treasurerdashboardFadeUp {
        from {
          opacity: 0;
          transform: translateY(10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes treasurerdashboardBellShake {
        0%,
        100% {
          transform: rotate(0deg);
        }
        20% {
          transform: rotate(14deg);
        }
        40% {
          transform: rotate(-12deg);
        }
        60% {
          transform: rotate(8deg);
        }
        80% {
          transform: rotate(-6deg);
        }
      }

      .treasurerdashboard-container {
        display: flex;
        min-height: 100vh;
      }

      .treasurerdashboard-sidebar {
        width: var(--sidebar-w);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 40;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background:
          radial-gradient(
            circle at top left,
            rgba(204, 255, 0, 0.14),
            transparent 28%
          ),
          linear-gradient(180deg, #051650 0%, #081d63 56%, #040f3b 100%);
        box-shadow: var(--shadow-sidebar);
        transition: width 0.25s ease;
      }

      .treasurerdashboard-sidebar.mini {
        width: var(--sidebar-mini);
      }

      .treasurerdashboard-toggle-wrap {
        position: fixed;
        top: 50%;
        left: var(--sidebar-w);
        transform: translate(-50%, -50%);
        z-index: 200;
        transition: left 0.25s ease;
      }

      .treasurerdashboard-toggle-wrap.mini {
        left: var(--sidebar-mini);
      }

      .treasurerdashboard-toggle-btn {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: var(--surface);
        border: 1px solid var(--border);
        box-shadow: 0 5px 12px rgba(5, 22, 80, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        transition:
          background 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurerdashboard-toggle-btn:hover {
        background: var(--lime);
        border-color: var(--lime);
        transform: scale(1.05);
      }

      .treasurerdashboard-toggle-btn i {
        font-size: 10px;
        color: var(--navy);
        transition: transform 0.25s ease;
      }

      .treasurerdashboard-toggle-wrap.mini .treasurerdashboard-toggle-btn i {
        transform: rotate(180deg);
      }

      .treasurerdashboard-identity {
        height: var(--topbar-h);
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 0 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
        overflow: hidden;
      }

      .treasurerdashboard-identity-logo {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        overflow: hidden;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.12);
      }

      .treasurerdashboard-identity-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .treasurerdashboard-identity-name {
        font-size: 14px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.2;
      }

      .treasurerdashboard-identity-chip {
        display: inline-flex;
        align-items: center;
        margin-top: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        background: var(--lime);
        color: var(--navy);
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
      }

      .treasurerdashboard-sidebar.mini .treasurerdashboard-identity-name,
      .treasurerdashboard-sidebar.mini .treasurerdashboard-identity-chip {
        opacity: 0;
        width: 0;
        pointer-events: none;
      }

      .treasurerdashboard-sidebar.mini .treasurerdashboard-identity-logo {
        margin: 0 auto;
      }

      .treasurerdashboard-menu {
        flex: 1;
        padding: 16px 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        overflow-y: auto;
        overflow-x: hidden;
      }

      .treasurerdashboard-menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.08);
        margin: 8px;
        flex-shrink: 0;
      }

      .treasurerdashboard-menu-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 13px;
        border-radius: 16px;
        font-size: 13px;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.66);
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        flex-shrink: 0;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurerdashboard-menu-link:hover {
        background: rgba(255, 255, 255, 0.09);
        color: #ffffff;
        transform: translateX(3px);
      }

      .treasurerdashboard-menu-link.active {
        background: var(--lime);
        color: var(--navy);
      }

      .treasurerdashboard-menu-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
      }

      .treasurerdashboard-menu-left i {
        width: 18px;
        text-align: center;
        font-size: 13px;
        flex-shrink: 0;
      }

      .treasurerdashboard-menu-label {
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .treasurerdashboard-sidebar.mini .treasurerdashboard-menu-label {
        opacity: 0;
        width: 0;
        pointer-events: none;
      }

      .treasurerdashboard-menu-count {
        font-size: 10px;
        font-weight: 800;
        background: rgba(255, 255, 255, 0.15);
        color: rgba(255, 255, 255, 0.85);
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .treasurerdashboard-menu-link.active .treasurerdashboard-menu-count {
        background: rgba(5, 22, 80, 0.18);
        color: var(--navy);
      }

      .treasurerdashboard-sidebar.mini .treasurerdashboard-menu-count {
        opacity: 0;
        pointer-events: none;
      }

      .treasurerdashboard-sidebar-footer {
        padding: 13px 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
      }

      .treasurerdashboard-logout {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 13px;
        border-radius: 16px;
        font-size: 13px;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.46);
        background: transparent;
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: left;
        white-space: nowrap;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurerdashboard-logout:hover {
        background: rgba(239, 68, 68, 0.14);
        color: #fca5a5;
        transform: translateX(3px);
      }

      .treasurerdashboard-main {
        margin-left: var(--sidebar-w);
        flex: 1;
        min-height: 100vh;
        min-width: 0;
        transition: margin-left 0.25s ease;
      }

      .treasurerdashboard-main.shifted {
        margin-left: var(--sidebar-mini);
      }

      .treasurerdashboard-topbar {
        height: var(--topbar-h);
        background: #ffffff;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 0 24px;
        position: sticky;
        top: 0;
        z-index: 30;
      }

      .treasurerdashboard-topbar-search {
        flex: 1;
        max-width: 390px;
        height: 38px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 14px;
        background: var(--soft-bg);
        border: 1px solid var(--border);
        border-radius: 999px;
        transition:
          background 0.18s ease,
          border-color 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-topbar-search:focus-within {
        border-color: var(--navy);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(5, 22, 80, 0.08);
      }

      .treasurerdashboard-topbar-search i {
        color: var(--text-muted);
        font-size: 12px;
      }

      .treasurerdashboard-topbar-search input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 13px;
        color: var(--text);
      }

      .treasurerdashboard-topbar-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .treasurerdashboard-topbar-icon {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        background: var(--soft-bg);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        font-size: 13px;
        cursor: pointer;
        position: relative;
        transition:
          border-color 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-topbar-icon:hover {
        border-color: var(--navy);
        color: var(--navy);
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.08);
      }

      .treasurerdashboard-topbar-icon:hover i {
        animation: treasurerdashboardBellShake 0.55s ease;
      }

      .treasurerdashboard-notification-count {
        position: absolute;
        top: -6px;
        right: -5px;
        min-width: 17px;
        height: 17px;
        padding: 0 5px;
        border-radius: 999px;
        background: var(--lime);
        color: var(--navy);
        font-size: 10px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .treasurerdashboard-profile {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 13px 5px 5px;
        border: 1px solid var(--border);
        border-radius: 999px;
        background: var(--soft-bg);
        cursor: pointer;
        transition:
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-profile:hover {
        border-color: var(--navy);
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.08);
      }

      .treasurerdashboard-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: var(--navy);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 800;
        color: var(--lime);
        flex-shrink: 0;
      }

      .treasurerdashboard-profile-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
      }

      .treasurerdashboard-profile-role {
        font-size: 11px;
        color: var(--text-muted);
        line-height: 1;
      }

      .treasurerdashboard-body {
        padding: 24px 26px 44px;
      }

      .treasurerdashboard-hero {
        background:
          linear-gradient(
            135deg,
            rgba(5, 22, 80, 0.98),
            rgba(10, 33, 96, 0.95)
          ),
          radial-gradient(
            circle at top right,
            rgba(204, 255, 0, 0.24),
            transparent 35%
          );
        border-radius: 28px;
        padding: 26px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        animation: treasurerdashboardFadeUp 0.28s ease both;
        margin-bottom: 20px;
        transition:
          transform 0.2s ease,
          box-shadow 0.2s ease;
      }

      .treasurerdashboard-hero:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
      }

      .treasurerdashboard-hero::before {
        content: "";
        position: absolute;
        width: 210px;
        height: 210px;
        right: -90px;
        top: -110px;
        background: rgba(204, 255, 0, 0.12);
        border-radius: 50%;
      }

      .treasurerdashboard-hero::after {
        content: "";
        position: absolute;
        width: 150px;
        height: 150px;
        right: 90px;
        bottom: -90px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 50%;
      }

      .treasurerdashboard-hero-text,
      .treasurerdashboard-hero-actions {
        position: relative;
        z-index: 1;
      }

      .treasurerdashboard-hero-text h2 {
        font-size: 22px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.25;
        margin-bottom: 0;
      }

      .treasurerdashboard-hero-text p {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.68);
        margin-top: 7px;
        max-width: 560px;
        margin-bottom: 0;
      }

      .treasurerdashboard-hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
      }

      .treasurerdashboard-btn {
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-btn:hover {
        transform: translateY(-1px);
      }

      .treasurerdashboard-btn-primary {
        background: var(--navy);
        color: #ffffff;
      }

      .treasurerdashboard-btn-primary:hover {
        background: var(--lime);
        color: var(--navy);
        box-shadow: 0 8px 16px rgba(204, 255, 0, 0.18);
      }

      .treasurerdashboard-btn-lime {
        background: var(--lime);
        color: var(--navy);
      }

      .treasurerdashboard-btn-lime:hover {
        background: #b7e900;
        color: var(--navy);
        box-shadow: 0 8px 16px rgba(204, 255, 0, 0.18);
      }

      .treasurerdashboard-btn-light {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.18);
      }

      .treasurerdashboard-btn-light:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
      }

      .treasurerdashboard-panel,
      .treasurerdashboard-side-panel,
      .treasurerdashboard-transaction-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 28px;
        box-shadow: var(--shadow-light);
        animation: treasurerdashboardFadeUp 0.28s ease both;
        transition:
          box-shadow 0.2s ease,
          transform 0.2s ease,
          border-color 0.2s ease;
      }

      .treasurerdashboard-panel:hover,
      .treasurerdashboard-side-panel:hover,
      .treasurerdashboard-transaction-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-3px);
        border-color: #d7deea;
      }

      .treasurerdashboard-panel {
        overflow: hidden;
      }

      .treasurerdashboard-panel-head {
        padding: 22px 24px 16px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
      }

      .treasurerdashboard-panel-title h3 {
        font-size: 18px;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 4px;
      }

      .treasurerdashboard-panel-title p {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 0;
      }

      .treasurerdashboard-panel-link {
        min-height: 38px;
        padding: 0 15px;
        border-radius: 999px;
        background: var(--soft-bg);
        border: 1px solid var(--border);
        color: var(--navy);
        font-size: 12px;
        font-weight: 800;
        display: flex;
        align-items: center;
        white-space: nowrap;
        transition:
          background 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-panel-link:hover {
        background: #ffffff;
        border-color: var(--navy);
        color: var(--navy);
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.06);
      }

      .treasurerdashboard-chart-area {
        padding: 22px 24px 24px;
      }

      .treasurerdashboard-chart-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
      }

      .treasurerdashboard-chart-total {
        font-size: 28px;
        font-weight: 800;
        color: var(--navy);
        line-height: 1;
      }

      .treasurerdashboard-chart-sub {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        margin-top: 6px;
      }

      .treasurerdashboard-chart-chip {
        min-height: 34px;
        padding: 0 13px;
        border-radius: 999px;
        background: var(--soft-navy-bg);
        color: var(--navy);
        border: 1px solid var(--border);
        font-size: 12px;
        font-weight: 800;
        display: flex;
        align-items: center;
        white-space: nowrap;
      }

      .treasurerdashboard-chart-board {
        background: linear-gradient(180deg, #fbfcff 0%, #f7f9fd 100%);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 18px 18px 14px;
      }

      .treasurerdashboard-chart-grid {
        display: grid;
        grid-template-columns: 52px 1fr;
        gap: 12px;
        min-height: 250px;
      }

      .treasurerdashboard-chart-y {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: flex-end;
        padding-top: 2px;
        padding-bottom: 34px;
      }

      .treasurerdashboard-chart-y span {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
      }

      .treasurerdashboard-chart-plot {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0;
        min-width: 0;
      }

      .treasurerdashboard-chart-lines {
        position: absolute;
        inset: 0 0 34px 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        pointer-events: none;
      }

      .treasurerdashboard-chart-lines span {
        display: block;
        width: 100%;
        border-top: 1px dashed #d8dfec;
      }

      .treasurerdashboard-chart-bars {
        height: 210px;
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        padding: 10px 10px 0;
      }

      .treasurerdashboard-chart-group {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
      }

      .treasurerdashboard-chart-value {
        font-size: 11px;
        font-weight: 800;
        color: var(--navy);
        white-space: nowrap;
        opacity: 0.9;
      }

      .treasurerdashboard-chart-bar-wrap {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        height: 150px;
      }

      .treasurerdashboard-chart-bar {
        width: 42px;
        max-width: 100%;
        border-radius: 18px 18px 10px 10px;
        background: linear-gradient(180deg, #17338a 0%, #051650 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
        position: relative;
        cursor: pointer;
        transition:
          transform 0.18s ease,
          filter 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-chart-bar::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 8px;
        transform: translateX(-50%);
        width: 60%;
        height: 4px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
      }

      .treasurerdashboard-chart-bar:hover {
        transform: translateY(-5px);
        filter: brightness(1.08);
        box-shadow: 0 10px 22px rgba(5, 22, 80, 0.14);
      }

      .treasurerdashboard-chart-bar.current {
        background: linear-gradient(180deg, #ccff00 0%, #dff76b 100%);
        border: 2px solid var(--navy);
      }

      .treasurerdashboard-chart-bar.current::after {
        background: rgba(5, 22, 80, 0.18);
      }

      .treasurerdashboard-chart-month {
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        white-space: nowrap;
      }

      .treasurerdashboard-filter-bar {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .treasurerdashboard-chip {
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--soft-bg);
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-chip:hover {
        border-color: var(--navy);
        color: var(--navy);
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.06);
      }

      .treasurerdashboard-chip.on {
        background: var(--navy);
        border-color: var(--navy);
        color: var(--lime);
      }

      .treasurerdashboard-table-wrap {
        overflow-x: auto;
      }

      .treasurerdashboard-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
      }

      .treasurerdashboard-table thead th {
        background: #fafbfd;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--text-muted);
        padding: 12px 16px;
        text-align: left;
        white-space: nowrap;
        border-bottom: 1px solid var(--border);
      }

      .treasurerdashboard-table tbody tr {
        border-bottom: 1px solid var(--border);
        transition:
          background 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-table tbody tr:hover {
        background: #fbfdff;
        box-shadow: inset 4px 0 0 var(--lime);
      }

      .treasurerdashboard-table td {
        padding: 13px 16px;
        vertical-align: middle;
        color: var(--text);
      }

      .treasurerdashboard-document-name {
        font-size: 13px;
        font-weight: 800;
        color: var(--navy);
        white-space: nowrap;
      }

      .treasurerdashboard-money-text {
        font-size: 13px;
        font-weight: 800;
        color: var(--navy);
        white-space: nowrap;
      }

      .treasurerdashboard-progress-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 120px;
      }

      .treasurerdashboard-progress-track {
        height: 8px;
        flex: 1;
        background: #eaf0f7;
        border-radius: 999px;
        overflow: hidden;
      }

      .treasurerdashboard-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--navy), #254ca8);
      }

      .treasurerdashboard-progress-text {
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        min-width: 34px;
        text-align: right;
      }

      .treasurerdashboard-table-foot {
        padding: 15px 22px 18px;
        border-top: 1px solid var(--border);
        background: #fafbfd;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
      }

      .treasurerdashboard-foot-info {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 600;
      }

      .treasurerdashboard-foot-total {
        font-size: 13px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurerdashboard-transaction-list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        padding: 18px;
      }

      .treasurerdashboard-transaction-card {
        padding: 16px;
      }

      .treasurerdashboard-transaction-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
      }

      .treasurerdashboard-transaction-code {
        font-size: 12px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurerdashboard-transaction-date {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
      }

      .treasurerdashboard-transaction-name {
        font-size: 14px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 5px;
      }

      .treasurerdashboard-transaction-document {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 12px;
      }

      .treasurerdashboard-transaction-amount {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        background: var(--soft-navy-bg);
        color: var(--navy);
        font-size: 12px;
        font-weight: 800;
      }

      .treasurerdashboard-side-stack {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }

      .treasurerdashboard-side-panel {
        padding: 20px;
      }

      .treasurerdashboard-side-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
      }

      .treasurerdashboard-side-head h3 {
        font-size: 16px;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 0;
      }

      .treasurerdashboard-side-head span {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 700;
      }

      .treasurerdashboard-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
      }

      .treasurerdashboard-summary-card {
        border: 1px solid var(--border);
        border-radius: 22px;
        background: #ffffff;
        padding: 14px;
        transition:
          box-shadow 0.2s ease,
          transform 0.2s ease,
          border-color 0.2s ease;
      }

      .treasurerdashboard-summary-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-3px);
        border-color: #d7deea;
      }

      .treasurerdashboard-summary-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
      }

      .treasurerdashboard-summary-label {
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        line-height: 1.35;
      }

      .treasurerdashboard-summary-icon {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--soft-bg);
        color: var(--navy);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .treasurerdashboard-summary-icon i {
        font-size: 16px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .treasurerdashboard-summary-value {
        font-size: 20px;
        font-weight: 800;
        color: var(--navy);
        line-height: 1;
        margin-bottom: 6px;
        letter-spacing: -0.4px;
      }

      .treasurerdashboard-summary-note {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 600;
      }

      .treasurerdashboard-income-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .treasurerdashboard-income-row {
        display: flex;
        flex-direction: column;
        gap: 6px;
        transition: transform 0.18s ease;
      }

      .treasurerdashboard-income-row:hover {
        transform: translateX(3px);
      }

      .treasurerdashboard-income-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
      }

      .treasurerdashboard-income-name {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text);
      }

      .treasurerdashboard-income-amount {
        font-size: 12px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurerdashboard-bar-track {
        height: 8px;
        background: #eaf0f7;
        border-radius: 999px;
        overflow: hidden;
      }

      .treasurerdashboard-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--navy), #254ca8);
      }

      .treasurerdashboard-note-box {
        padding: 14px;
        border-radius: 20px;
        background: var(--soft-bg);
        border: 1px solid var(--border);
        transition:
          background 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurerdashboard-note-box:hover {
        background: #ffffff;
        border-color: #d7deea;
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(5, 22, 80, 0.08);
      }

      .treasurerdashboard-note-icon {
        width: 42px;
        height: 42px;
        border-radius: 17px;
        background: var(--navy);
        color: var(--lime);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
      }

      .treasurerdashboard-note-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 4px;
      }

      .treasurerdashboard-note-text {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 0;
      }

      @media (max-width: 1200px) {
        .treasurerdashboard-summary-grid {
          grid-template-columns: 1fr 1fr;
        }
      }

      @media (max-width: 992px) {
        .treasurerdashboard-side-stack {
          margin-top: 16px;
        }

        .treasurerdashboard-panel-head {
          flex-direction: column;
          align-items: flex-start;
        }

        .treasurerdashboard-hero {
          flex-direction: column;
          align-items: flex-start;
        }

        .treasurerdashboard-transaction-list {
          grid-template-columns: 1fr;
        }

        .treasurerdashboard-chart-grid {
          grid-template-columns: 42px 1fr;
        }

        .treasurerdashboard-chart-bars {
          gap: 12px;
          padding: 10px 4px 0;
        }

        .treasurerdashboard-chart-bar {
          width: 34px;
        }
      }

      @media (max-width: 760px) {
        .treasurerdashboard-sidebar {
          width: var(--sidebar-mini);
        }

        .treasurerdashboard-main {
          margin-left: var(--sidebar-mini);
        }

        .treasurerdashboard-toggle-wrap {
          left: var(--sidebar-mini);
        }

        .treasurerdashboard-identity-name,
        .treasurerdashboard-identity-chip,
        .treasurerdashboard-menu-label,
        .treasurerdashboard-menu-count {
          opacity: 0;
          width: 0;
          pointer-events: none;
        }

        .treasurerdashboard-body {
          padding: 18px 14px 36px;
        }

        .treasurerdashboard-topbar {
          padding: 0 14px;
        }

        .treasurerdashboard-profile-name,
        .treasurerdashboard-profile-role {
          display: none;
        }

        .treasurerdashboard-topbar-search {
          max-width: none;
        }

        .treasurerdashboard-hero-actions {
          width: 100%;
        }

        .treasurerdashboard-hero-actions .treasurerdashboard-btn {
          flex: 1;
        }

        .treasurerdashboard-summary-grid {
          grid-template-columns: 1fr;
        }

        .treasurerdashboard-chart-top {
          flex-direction: column;
          align-items: flex-start;
        }

        .treasurerdashboard-chart-grid {
          grid-template-columns: 36px 1fr;
          gap: 8px;
        }

        .treasurerdashboard-chart-y span {
          font-size: 10px;
        }

        .treasurerdashboard-chart-bars {
          gap: 8px;
        }

        .treasurerdashboard-chart-value {
          font-size: 10px;
        }

        .treasurerdashboard-chart-bar {
          width: 28px;
        }

        .treasurerdashboard-chart-month {
          font-size: 10px;
        }
      }
      .treasurerdashboard-transaction-icon {
        margin-right: 6px;
      }

      .treasurerdashboard-transaction-icon i {
        transform: translateY(1px);
      }
    </style>
  </head>

  <body>
    <div
      class="treasurerdashboard-toggle-wrap"
      id="treasurerdashboard-toggle-wrap"
    >
      <button
        class="treasurerdashboard-toggle-btn"
        id="treasurerdashboard-toggle-button"
        title="Collapse / Expand"
      >
        <i class="fa-solid fa-chevron-left"></i>
      </button>
    </div>

    <div class="treasurerdashboard-container">
      <aside class="treasurerdashboard-sidebar" id="treasurerdashboard-sidebar">
        <div class="treasurerdashboard-identity">
          <div class="treasurerdashboard-identity-logo">
            <img src="alapan.png" alt="Alapan logo" />
          </div>

          <div>
            <div class="treasurerdashboard-identity-name">BarangayKonek</div>
            <span class="treasurerdashboard-identity-chip"
              >Treasurer Portal</span
            >
          </div>
        </div>

        <nav class="treasurerdashboard-menu">
          <a
            href="treasurerdashboard.html"
            class="treasurerdashboard-menu-link active"
          >
            <div class="treasurerdashboard-menu-left">
              <i class="fa-solid fa-house"></i>
              <span class="treasurerdashboard-menu-label">Dashboard</span>
            </div>
          </a>

          <a
            href="treasurertransaction.html"
            class="treasurerdashboard-menu-link"
          >
            <div class="treasurerdashboard-menu-left">
              <i class="fa-solid fa-coins"></i>
              <span class="treasurerdashboard-menu-label">Transactions</span>
            </div>
            <span class="treasurerdashboard-menu-count">8</span>
          </a>

          <a href="treasurerhistory.html" class="treasurerdashboard-menu-link">
            <div class="treasurerdashboard-menu-left">
              <i class="fa-solid fa-clock-rotate-left"></i>
              <span class="treasurerdashboard-menu-label"
                >Transaction History</span
              >
            </div>
          </a>

          <a
            href="treasurercommunity.html"
            class="treasurerdashboard-menu-link"
          >
            <div class="treasurerdashboard-menu-left">
              <i class="fa-solid fa-people-group"></i>
              <span class="treasurerdashboard-menu-label">Community</span>
            </div>
          </a>

          <div class="treasurerdashboard-menu-divider"></div>

          <a href="#" class="treasurerdashboard-menu-link">
            <div class="treasurerdashboard-menu-left">
              <i class="fa-solid fa-gear"></i>
              <span class="treasurerdashboard-menu-label">Settings</span>
            </div>
          </a>
        </nav>

        <div class="treasurerdashboard-sidebar-footer">
          <button class="treasurerdashboard-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="treasurerdashboard-menu-label">Log Out</span>
          </button>
        </div>
      </aside>

      <main class="treasurerdashboard-main" id="treasurerdashboard-main">
        <header class="treasurerdashboard-topbar">
          <div class="treasurerdashboard-topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              placeholder="Search residents, transactions, records..."
            />
          </div>

          <div class="treasurerdashboard-topbar-right">
            <div class="treasurerdashboard-topbar-icon">
              <i class="fa-solid fa-bell"></i>
              <span class="treasurerdashboard-notification-count">2</span>
            </div>

            <div class="treasurerdashboard-profile">
              <div class="treasurerdashboard-avatar">LR</div>
              <div>
                <div class="treasurerdashboard-profile-name">Luz Reyes</div>
                <div class="treasurerdashboard-profile-role">Treasurer</div>
              </div>
            </div>
          </div>
        </header>

        <div class="treasurerdashboard-body">
          <section class="treasurerdashboard-hero">
            <div class="treasurerdashboard-hero-text">
              <h2>Good morning, Treasurer Luz</h2>
              <p>
                Monitor income, review transaction records, and prepare monthly
                financial summaries for barangay document services.
              </p>
            </div>

            <div class="treasurerdashboard-hero-actions">
              <button
                class="treasurerdashboard-btn treasurerdashboard-btn-lime"
              >
                <i class="fa-solid fa-plus"></i>
                New Transaction
              </button>

              <button
                class="treasurerdashboard-btn treasurerdashboard-btn-light"
              >
                <i class="fa-solid fa-file-export"></i>
                Export
              </button>
            </div>
          </section>

          <div class="row g-3">
            <div class="col-lg-8 col-xl-9">
              <section class="treasurerdashboard-panel">
                <div class="treasurerdashboard-panel-head">
                  <div class="treasurerdashboard-panel-title">
                    <h3>Monthly Income</h3>
                    <p>Income movement from document service transactions.</p>
                  </div>

                  <a
                    href="treasurerhistory.html"
                    class="treasurerdashboard-panel-link"
                  >
                    View History
                  </a>
                </div>

                <div class="treasurerdashboard-chart-area">
                  <div class="treasurerdashboard-chart-top">
                    <div>
                      <div class="treasurerdashboard-chart-total">₱13,100</div>
                      <div class="treasurerdashboard-chart-sub">
                        April 2026 income
                      </div>
                    </div>

                    <div class="treasurerdashboard-chart-chip">
                      Current month
                    </div>
                  </div>

                  <div class="treasurerdashboard-chart-board">
                    <div class="treasurerdashboard-chart-grid">
                      <div class="treasurerdashboard-chart-y">
                        <span>₱15k</span>
                        <span>₱10k</span>
                        <span>₱5k</span>
                        <span>₱0</span>
                      </div>

                      <div class="treasurerdashboard-chart-plot">
                        <div class="treasurerdashboard-chart-lines">
                          <span></span>
                          <span></span>
                          <span></span>
                          <span></span>
                        </div>

                        <div class="treasurerdashboard-chart-bars">
                          <div class="treasurerdashboard-chart-group">
                            <div class="treasurerdashboard-chart-value">
                              ₱6.2k
                            </div>
                            <div class="treasurerdashboard-chart-bar-wrap">
                              <div
                                class="treasurerdashboard-chart-bar"
                                style="height: 62px"
                              ></div>
                            </div>
                            <div class="treasurerdashboard-chart-month">
                              Jan
                            </div>
                          </div>

                          <div class="treasurerdashboard-chart-group">
                            <div class="treasurerdashboard-chart-value">
                              ₱8.0k
                            </div>
                            <div class="treasurerdashboard-chart-bar-wrap">
                              <div
                                class="treasurerdashboard-chart-bar"
                                style="height: 80px"
                              ></div>
                            </div>
                            <div class="treasurerdashboard-chart-month">
                              Feb
                            </div>
                          </div>

                          <div class="treasurerdashboard-chart-group">
                            <div class="treasurerdashboard-chart-value">
                              ₱7.1k
                            </div>
                            <div class="treasurerdashboard-chart-bar-wrap">
                              <div
                                class="treasurerdashboard-chart-bar"
                                style="height: 71px"
                              ></div>
                            </div>
                            <div class="treasurerdashboard-chart-month">
                              Mar
                            </div>
                          </div>

                          <div class="treasurerdashboard-chart-group">
                            <div class="treasurerdashboard-chart-value">
                              ₱13.1k
                            </div>
                            <div class="treasurerdashboard-chart-bar-wrap">
                              <div
                                class="treasurerdashboard-chart-bar current"
                                style="height: 131px"
                              ></div>
                            </div>
                            <div class="treasurerdashboard-chart-month">
                              Apr
                            </div>
                          </div>

                          <div class="treasurerdashboard-chart-group">
                            <div class="treasurerdashboard-chart-value">
                              ₱8.4k
                            </div>
                            <div class="treasurerdashboard-chart-bar-wrap">
                              <div
                                class="treasurerdashboard-chart-bar"
                                style="height: 84px"
                              ></div>
                            </div>
                            <div class="treasurerdashboard-chart-month">
                              May
                            </div>
                          </div>

                          <div class="treasurerdashboard-chart-group">
                            <div class="treasurerdashboard-chart-value">
                              ₱7.8k
                            </div>
                            <div class="treasurerdashboard-chart-bar-wrap">
                              <div
                                class="treasurerdashboard-chart-bar"
                                style="height: 78px"
                              ></div>
                            </div>
                            <div class="treasurerdashboard-chart-month">
                              Jun
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="treasurerdashboard-panel mt-3">
                <div class="treasurerdashboard-panel-head">
                  <div class="treasurerdashboard-panel-title">
                    <h3>Income by Document Type</h3>
                    <p>Summary of document income for this month.</p>
                  </div>

                  <button
                    class="treasurerdashboard-btn treasurerdashboard-btn-primary"
                  >
                    Export
                  </button>
                </div>

                <div class="treasurerdashboard-filter-bar">
                  <button class="treasurerdashboard-chip on">All</button>
                  <button class="treasurerdashboard-chip">Clearance</button>
                  <button class="treasurerdashboard-chip">Residency</button>
                  <button class="treasurerdashboard-chip">Business</button>
                </div>

                <div class="treasurerdashboard-table-wrap">
                  <table class="treasurerdashboard-table">
                    <thead>
                      <tr>
                        <th>Document Type</th>
                        <th>Fee</th>
                        <th>Issued</th>
                        <th>Income</th>
                        <th>Share</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr>
                        <td>
                          <span class="treasurerdashboard-document-name"
                            >Barangay Clearance</span
                          >
                        </td>
                        <td>₱100</td>
                        <td>54</td>
                        <td>
                          <span class="treasurerdashboard-money-text"
                            >₱5,400</span
                          >
                        </td>
                        <td>
                          <div class="treasurerdashboard-progress-wrap">
                            <div class="treasurerdashboard-progress-track">
                              <div
                                class="treasurerdashboard-progress-fill"
                                style="width: 73%"
                              ></div>
                            </div>
                            <span class="treasurerdashboard-progress-text"
                              >73%</span
                            >
                          </div>
                        </td>
                      </tr>

                      <tr>
                        <td>
                          <span class="treasurerdashboard-document-name"
                            >Business Permit</span
                          >
                        </td>
                        <td>₱200</td>
                        <td>16</td>
                        <td>
                          <span class="treasurerdashboard-money-text"
                            >₱3,200</span
                          >
                        </td>
                        <td>
                          <div class="treasurerdashboard-progress-wrap">
                            <div class="treasurerdashboard-progress-track">
                              <div
                                class="treasurerdashboard-progress-fill"
                                style="width: 43%"
                              ></div>
                            </div>
                            <span class="treasurerdashboard-progress-text"
                              >43%</span
                            >
                          </div>
                        </td>
                      </tr>

                      <tr>
                        <td>
                          <span class="treasurerdashboard-document-name"
                            >Certificate of Residency</span
                          >
                        </td>
                        <td>₱50</td>
                        <td>38</td>
                        <td>
                          <span class="treasurerdashboard-money-text"
                            >₱1,900</span
                          >
                        </td>
                        <td>
                          <div class="treasurerdashboard-progress-wrap">
                            <div class="treasurerdashboard-progress-track">
                              <div
                                class="treasurerdashboard-progress-fill"
                                style="width: 51%"
                              ></div>
                            </div>
                            <span class="treasurerdashboard-progress-text"
                              >51%</span
                            >
                          </div>
                        </td>
                      </tr>

                      <tr>
                        <td>
                          <span class="treasurerdashboard-document-name"
                            >Certificate of Good Moral</span
                          >
                        </td>
                        <td>₱100</td>
                        <td>18</td>
                        <td>
                          <span class="treasurerdashboard-money-text"
                            >₱1,800</span
                          >
                        </td>
                        <td>
                          <div class="treasurerdashboard-progress-wrap">
                            <div class="treasurerdashboard-progress-track">
                              <div
                                class="treasurerdashboard-progress-fill"
                                style="width: 24%"
                              ></div>
                            </div>
                            <span class="treasurerdashboard-progress-text"
                              >24%</span
                            >
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="treasurerdashboard-table-foot">
                  <span class="treasurerdashboard-foot-info"
                    >Showing 4 document income records</span
                  >
                  <span class="treasurerdashboard-foot-total"
                    >Total: ₱13,100</span
                  >
                </div>
              </section>

              <section class="treasurerdashboard-panel mt-3">
                <div class="treasurerdashboard-panel-head">
                  <div class="treasurerdashboard-panel-title">
                    <h3>Transaction History</h3>
                    <p>Recently recorded payment transactions.</p>
                  </div>

                  <a
                    href="treasurerhistory.html"
                    class="treasurerdashboard-panel-link"
                  >
                    View All
                  </a>
                </div>

                <div class="treasurerdashboard-transaction-list">
                  <article class="treasurerdashboard-transaction-card">
                    <div class="treasurerdashboard-transaction-top">
                      <span class="treasurerdashboard-transaction-code"
                        >TRN-2026-041</span
                      >
                      <span class="treasurerdashboard-transaction-date"
                        >Apr 21</span
                      >
                    </div>

                    <div class="treasurerdashboard-transaction-name">
                      Ana Lopez
                    </div>
                    <div class="treasurerdashboard-transaction-document">
                      Barangay Clearance
                    </div>
                    <span class="treasurerdashboard-transaction-amount"
                      >₱100</span
                    >
                  </article>

                  <article class="treasurerdashboard-transaction-card">
                    <div class="treasurerdashboard-transaction-top">
                      <span class="treasurerdashboard-transaction-code"
                        >TRN-2026-040</span
                      >
                      <span class="treasurerdashboard-transaction-date"
                        >Apr 20</span
                      >
                    </div>

                    <div class="treasurerdashboard-transaction-name">
                      Ryan Gomez
                    </div>
                    <div class="treasurerdashboard-transaction-document">
                      Certificate of Residency
                    </div>
                    <span class="treasurerdashboard-transaction-amount"
                      >₱50</span
                    >
                  </article>

                  <article class="treasurerdashboard-transaction-card">
                    <div class="treasurerdashboard-transaction-top">
                      <span class="treasurerdashboard-transaction-code"
                        >TRN-2026-039</span
                      >
                      <span class="treasurerdashboard-transaction-date"
                        >Apr 19</span
                      >
                    </div>

                    <div class="treasurerdashboard-transaction-name">
                      Mark Rivera
                    </div>
                    <div class="treasurerdashboard-transaction-document">
                      Business Permit
                    </div>
                    <span class="treasurerdashboard-transaction-amount"
                      >₱200</span
                    >
                  </article>
                </div>
              </section>
            </div>

            <div class="col-lg-4 col-xl-3">
              <div class="treasurerdashboard-side-stack">
                <section class="treasurerdashboard-side-panel">
                  <div class="treasurerdashboard-side-head">
                    <h3>Quick Summary</h3>
                    <span>Today</span>
                  </div>

                  <div class="treasurerdashboard-summary-grid">
                    <div class="treasurerdashboard-summary-card">
                      <div class="treasurerdashboard-summary-top">
                        <div class="treasurerdashboard-summary-label">
                          Total Income
                        </div>
                      </div>
                      <div class="treasurerdashboard-summary-value">₱13.1K</div>
                      <div class="treasurerdashboard-summary-note">
                        This month
                      </div>
                    </div>

                    <div class="treasurerdashboard-summary-card">
                      <div class="treasurerdashboard-summary-top">
                        <div class="treasurerdashboard-summary-label">
                          Transactions
                        </div>
                      </div>
                      <div class="treasurerdashboard-summary-value">86</div>
                      <div class="treasurerdashboard-summary-note">
                        Paid records
                      </div>
                    </div>

                    <div class="treasurerdashboard-summary-card">
                      <div class="treasurerdashboard-summary-top">
                        <div class="treasurerdashboard-summary-label">
                          Pending Payment
                        </div>
                      </div>
                      <div class="treasurerdashboard-summary-value">₱1.2K</div>
                      <div class="treasurerdashboard-summary-note">
                        Needs checking
                      </div>
                    </div>

                    <div class="treasurerdashboard-summary-card">
                      <div class="treasurerdashboard-summary-top">
                        <div class="treasurerdashboard-summary-label">
                          Waived Records
                        </div>
                      </div>
                      <div class="treasurerdashboard-summary-value">22</div>
                      <div class="treasurerdashboard-summary-note">
                        No payment
                      </div>
                    </div>
                  </div>
                </section>

                <section class="treasurerdashboard-side-panel">
                  <div class="treasurerdashboard-side-head">
                    <h3>Income Sources</h3>
                    <span>This month</span>
                  </div>

                  <div class="treasurerdashboard-income-list">
                    <div class="treasurerdashboard-income-row">
                      <div class="treasurerdashboard-income-top">
                        <span class="treasurerdashboard-income-name"
                          >Barangay Clearance</span
                        >
                        <span class="treasurerdashboard-income-amount"
                          >₱5,400</span
                        >
                      </div>
                      <div class="treasurerdashboard-bar-track">
                        <div
                          class="treasurerdashboard-bar-fill"
                          style="width: 75%"
                        ></div>
                      </div>
                    </div>

                    <div class="treasurerdashboard-income-row">
                      <div class="treasurerdashboard-income-top">
                        <span class="treasurerdashboard-income-name"
                          >Business Permit</span
                        >
                        <span class="treasurerdashboard-income-amount"
                          >₱3,200</span
                        >
                      </div>
                      <div class="treasurerdashboard-bar-track">
                        <div
                          class="treasurerdashboard-bar-fill"
                          style="width: 60%"
                        ></div>
                      </div>
                    </div>

                    <div class="treasurerdashboard-income-row">
                      <div class="treasurerdashboard-income-top">
                        <span class="treasurerdashboard-income-name"
                          >Certificate of Residency</span
                        >
                        <span class="treasurerdashboard-income-amount"
                          >₱1,900</span
                        >
                      </div>
                      <div class="treasurerdashboard-bar-track">
                        <div
                          class="treasurerdashboard-bar-fill"
                          style="width: 45%"
                        ></div>
                      </div>
                    </div>
                  </div>
                </section>

                <section class="treasurerdashboard-side-panel">
                  <div class="treasurerdashboard-side-head">
                    <h3>Transaction Notes</h3>
                    <span>Today</span>
                  </div>

                  <div class="treasurerdashboard-note-box">
                    <div class="treasurerdashboard-note-icon">
                      <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="treasurerdashboard-note-title">
                      8 transactions need checking
                    </div>
                    <p class="treasurerdashboard-note-text">
                      Review pending payments before closing the daily report.
                    </p>
                  </div>
                </section>

                <section class="treasurerdashboard-side-panel">
                  <div class="treasurerdashboard-side-head">
                    <h3>Community</h3>
                    <span>3 new</span>
                  </div>

                  <div class="treasurerdashboard-note-box">
                    <div class="treasurerdashboard-note-icon">
                      <i class="fa-solid fa-people-group"></i>
                    </div>
                    <div class="treasurerdashboard-note-title">
                      Barangay Community Board
                    </div>
                    <p class="treasurerdashboard-note-text">
                      View resident posts, barangay updates, and community
                      activity from one place.
                    </p>
                  </div>

                  <button
                    class="treasurerdashboard-btn treasurerdashboard-btn-primary w-100 mt-3"
                  >
                    Open Community
                  </button>
                </section>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      var treasurerdashboardSidebar = document.getElementById(
        "treasurerdashboard-sidebar",
      );
      var treasurerdashboardMainArea = document.getElementById(
        "treasurerdashboard-main",
      );
      var treasurerdashboardToggleWrap = document.getElementById(
        "treasurerdashboard-toggle-wrap",
      );
      var treasurerdashboardToggleButton = document.getElementById(
        "treasurerdashboard-toggle-button",
      );

      treasurerdashboardToggleButton.addEventListener("click", function () {
        var treasurerdashboardIsSidebarMini =
          treasurerdashboardSidebar.classList.toggle("mini");
        treasurerdashboardMainArea.classList.toggle(
          "shifted",
          treasurerdashboardIsSidebarMini,
        );
        treasurerdashboardToggleWrap.classList.toggle(
          "mini",
          treasurerdashboardIsSidebarMini,
        );
      });

      var treasurerdashboardFilterButtons = document.querySelectorAll(
        ".treasurerdashboard-chip",
      );

      treasurerdashboardFilterButtons.forEach(
        function (treasurerdashboardButton) {
          treasurerdashboardButton.addEventListener("click", function () {
            treasurerdashboardFilterButtons.forEach(
              function (treasurerdashboardOtherButton) {
                treasurerdashboardOtherButton.classList.remove("on");
              },
            );

            treasurerdashboardButton.classList.add("on");
          });
        },
      );
    </script>
  </body>
</html>
