<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Treasurer Community — BarangayKonek</title>

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
        --soft-bg: #f8fafc;
        --page-bg: #eef3fb;
        --border: #e3e7f0;

        --text: #1a2240;
        --text-muted: #7a869a;

        --sidebar-w: 228px;
        --sidebar-mini: 64px;
        --topbar-h: 62px;

        --green-bg: #f0fdf4;
        --green-text: #166534;

        --blue-bg: #eff6ff;
        --blue-text: #1e40af;

        --amber-bg: #fffbeb;
        --amber-text: #92400e;

        --red-bg: #fef2f2;
        --red-text: #991b1b;

        --shadow-light: 0 8px 24px rgba(5, 22, 80, 0.08);
        --shadow-hover: 0 16px 34px rgba(5, 22, 80, 0.13);
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
      textarea,
      select {
        font-family: inherit;
      }

      @keyframes treasurercommunityFadeUp {
        from {
          opacity: 0;
          transform: translateY(10px);
        }

        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes treasurercommunityBellShake {
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

      .treasurercommunity-page {
        display: flex;
        min-height: 100vh;
      }

      .treasurercommunity-sidebar {
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

      .treasurercommunity-sidebar.mini {
        width: var(--sidebar-mini);
      }

      .treasurercommunity-toggle-wrap {
        position: fixed;
        top: 50%;
        left: var(--sidebar-w);
        transform: translate(-50%, -50%);
        z-index: 200;
        transition: left 0.25s ease;
      }

      .treasurercommunity-toggle-wrap.mini {
        left: var(--sidebar-mini);
      }

      .treasurercommunity-toggle-btn {
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

      .treasurercommunity-toggle-btn:hover {
        background: var(--lime);
        border-color: var(--lime);
        transform: scale(1.05);
      }

      .treasurercommunity-toggle-btn i {
        font-size: 10px;
        color: var(--navy);
        transition: transform 0.25s ease;
      }

      .treasurercommunity-toggle-wrap.mini .treasurercommunity-toggle-btn i {
        transform: rotate(180deg);
      }

      .treasurercommunity-identity {
        height: var(--topbar-h);
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 0 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
        overflow: hidden;
      }

      .treasurercommunity-identity-logo {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        overflow: hidden;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.12);
      }

      .treasurercommunity-identity-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .treasurercommunity-identity-name {
        font-size: 14px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.2;
      }

      .treasurercommunity-identity-chip {
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

      .treasurercommunity-sidebar.mini .treasurercommunity-identity-name,
      .treasurercommunity-sidebar.mini .treasurercommunity-identity-chip {
        opacity: 0;
        width: 0;
        pointer-events: none;
      }

      .treasurercommunity-sidebar.mini .treasurercommunity-identity-logo {
        margin: 0 auto;
      }

      .treasurercommunity-menu {
        flex: 1;
        padding: 16px 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        overflow-y: auto;
        overflow-x: hidden;
      }

      .treasurercommunity-menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.08);
        margin: 8px;
        flex-shrink: 0;
      }

      .treasurercommunity-menu-link {
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
        text-decoration: none;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurercommunity-menu-link:hover {
        background: rgba(255, 255, 255, 0.09);
        color: #ffffff;
        transform: translateX(3px);
      }

      .treasurercommunity-menu-link.active {
        background: var(--lime);
        color: var(--navy);
      }

      .treasurercommunity-menu-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
      }

      .treasurercommunity-menu-left i {
        width: 18px;
        text-align: center;
        font-size: 13px;
        flex-shrink: 0;
      }

      .treasurercommunity-menu-label {
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .treasurercommunity-sidebar.mini .treasurercommunity-menu-label {
        opacity: 0;
        width: 0;
        pointer-events: none;
      }

      .treasurercommunity-menu-count {
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

      .treasurercommunity-menu-link.active .treasurercommunity-menu-count {
        background: rgba(5, 22, 80, 0.18);
        color: var(--navy);
      }

      .treasurercommunity-sidebar.mini .treasurercommunity-menu-count {
        opacity: 0;
        pointer-events: none;
      }

      .treasurercommunity-sidebar-footer {
        padding: 13px 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
      }

      .treasurercommunity-logout {
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
        text-decoration: none;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurercommunity-logout:hover {
        background: rgba(239, 68, 68, 0.14);
        color: #fca5a5;
        transform: translateX(3px);
      }

      .treasurercommunity-main {
        margin-left: var(--sidebar-w);
        flex: 1;
        min-height: 100vh;
        min-width: 0;
        transition: margin-left 0.25s ease;
      }

      .treasurercommunity-main.shifted {
        margin-left: var(--sidebar-mini);
      }

      .treasurercommunity-topbar {
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

      .treasurercommunity-topbar-search {
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
          border-color 0.18s ease;
      }

      .treasurercommunity-topbar-search:focus-within {
        border-color: var(--navy);
        background: #ffffff;
      }

      .treasurercommunity-topbar-search i {
        color: var(--text-muted);
        font-size: 12px;
      }

      .treasurercommunity-topbar-search input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 13px;
        color: var(--text);
      }

      .treasurercommunity-topbar-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .treasurercommunity-notification-btn {
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
          background 0.18s ease;
      }

      .treasurercommunity-notification-btn:hover {
        border-color: var(--navy);
        color: var(--navy);
        background: #ffffff;
      }

      .treasurercommunity-notification-btn:hover i {
        animation: treasurercommunityBellShake 0.55s ease;
      }

      .treasurercommunity-notification-count {
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

      .treasurercommunity-profile {
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
          background 0.18s ease;
      }

      .treasurercommunity-profile:hover {
        border-color: var(--navy);
        background: #ffffff;
      }

      .treasurercommunity-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: var(--navy);
        color: var(--lime);
        font-size: 10px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .treasurercommunity-profile-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
      }

      .treasurercommunity-profile-role {
        font-size: 11px;
        color: var(--text-muted);
        line-height: 1;
      }

      .treasurercommunity-body {
        padding: 24px 26px 44px;
      }

      .treasurercommunity-page-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        animation: treasurercommunityFadeUp 0.28s ease both;
      }

      .treasurercommunity-page-title h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurercommunity-page-title p {
        margin: 6px 0 0;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
      }

      .treasurercommunity-page-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
      }

      .treasurercommunity-btn {
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        position: relative;
        overflow: hidden;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-btn::after,
      .treasurercommunity-action-btn::after,
      .treasurercommunity-tool-btn::after,
      .treasurercommunity-post-menu-btn::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.22);
        opacity: 0;
        transition: opacity 0.18s ease;
        pointer-events: none;
      }

      .treasurercommunity-btn:hover,
      .treasurercommunity-action-btn:hover,
      .treasurercommunity-tool-btn:hover,
      .treasurercommunity-post-menu-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.12);
      }

      .treasurercommunity-btn:hover::after,
      .treasurercommunity-action-btn:hover::after,
      .treasurercommunity-tool-btn:hover::after,
      .treasurercommunity-post-menu-btn:hover::after {
        opacity: 1;
      }

      .treasurercommunity-btn:active,
      .treasurercommunity-action-btn:active,
      .treasurercommunity-tool-btn:active,
      .treasurercommunity-post-menu-btn:active {
        transform: translateY(0);
        box-shadow: none;
      }

      .treasurercommunity-btn-navy {
        background: var(--navy);
        border-color: var(--navy);
        color: #ffffff;
      }

      .treasurercommunity-btn-navy:hover {
        background: var(--lime);
        border-color: var(--lime);
        color: var(--navy);
      }

      .treasurercommunity-btn-soft {
        background: #ffffff;
        border-color: var(--border);
        color: var(--navy);
      }

      .treasurercommunity-btn-soft:hover {
        background: var(--navy);
        border-color: var(--navy);
        color: #ffffff;
      }

      .treasurercommunity-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 28px;
        box-shadow: var(--shadow-light);
        animation: treasurercommunityFadeUp 0.28s ease both;
        transition:
          box-shadow 0.2s ease,
          transform 0.2s ease,
          border-color 0.2s ease;
      }

      .treasurercommunity-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
        border-color: #d7deea;
      }

      .treasurercommunity-composer {
        padding: 18px;
        margin-bottom: 16px;
      }

      .treasurercommunity-composer-top {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .treasurercommunity-composer-avatar,
      .treasurercommunity-post-avatar,
      .treasurercommunity-comment-avatar {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        background: var(--navy);
        color: var(--lime);
        font-size: 12px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .treasurercommunity-composer-input {
        flex: 1;
        min-height: 44px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--soft-bg);
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        padding: 0 16px;
        cursor: pointer;
        transition:
          border-color 0.18s ease,
          background 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurercommunity-composer-input:hover {
        border-color: var(--navy);
        background: #ffffff;
        color: var(--navy);
        transform: translateY(-1px);
      }

      .treasurercommunity-composer-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding-top: 14px;
        margin-top: 14px;
        border-top: 1px solid var(--border);
      }

      .treasurercommunity-tool-btn {
        min-height: 36px;
        padding: 0 13px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--soft-bg);
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-tool-btn:hover {
        background: var(--navy);
        color: #ffffff;
        border-color: var(--navy);
      }

      .treasurercommunity-post {
        padding: 20px;
        margin-bottom: 16px;
      }

      .treasurercommunity-post-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
      }

      .treasurercommunity-post-user {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
      }

      .treasurercommunity-post-name {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurercommunity-post-meta {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
      }

      .treasurercommunity-post-options {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
      }

      .treasurercommunity-post-menu-btn {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--soft-bg);
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-post-menu-btn:hover {
        background: var(--navy);
        color: #ffffff;
        border-color: var(--navy);
      }

      .treasurercommunity-dropdown-menu {
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: 0 12px 28px rgba(5, 22, 80, 0.14);
        padding: 8px;
      }

      .treasurercommunity-dropdown-item {
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        padding: 9px 11px;
        display: flex;
        align-items: center;
        gap: 9px;
      }

      .treasurercommunity-dropdown-item:hover {
        background: var(--soft-bg);
        color: var(--navy);
      }

      .treasurercommunity-dropdown-item.flag {
        color: var(--red-text);
      }

      .treasurercommunity-dropdown-item.flag:hover {
        background: var(--red-bg);
        color: var(--red-text);
      }

      .treasurercommunity-post-tag {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 11px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        background: var(--green-bg);
        color: var(--green-text);
      }

      .treasurercommunity-post-tag.resident {
        background: var(--blue-bg);
        color: var(--blue-text);
      }

      .treasurercommunity-post-tag.review {
        background: var(--amber-bg);
        color: var(--amber-text);
      }

      .treasurercommunity-post-text {
        font-size: 13px;
        color: var(--text);
        line-height: 1.6;
        margin-bottom: 14px;
      }

      .treasurercommunity-post-gallery {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
      }

      .treasurercommunity-post-photo {
        min-height: 150px;
        border-radius: 22px;
        background:
          linear-gradient(
            135deg,
            rgba(5, 22, 80, 0.08),
            rgba(204, 255, 0, 0.12)
          ),
          #f3f6fb;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 800;
        transition:
          transform 0.18s ease,
          border-color 0.18s ease;
      }

      .treasurercommunity-post-photo:hover {
        transform: scale(1.01);
        border-color: var(--navy);
      }

      .treasurercommunity-post-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 700;
      }

      .treasurercommunity-post-actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 12px;
      }

      .treasurercommunity-action-btn {
        min-height: 36px;
        border: none;
        border-radius: 999px;
        background: var(--soft-bg);
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        position: relative;
        overflow: hidden;
        transition:
          background 0.18s ease,
          color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-action-btn:hover {
        background: var(--navy);
        color: #ffffff;
      }

      .treasurercommunity-action-btn.flag:hover {
        background: var(--red-text);
        color: #ffffff;
      }

      .treasurercommunity-comment-box {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 14px;
      }

      .treasurercommunity-comment-input {
        flex: 1;
        height: 38px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--soft-bg);
        padding: 0 14px;
        font-size: 13px;
        outline: none;
      }

      .treasurercommunity-comment-input:focus {
        background: #ffffff;
        border-color: var(--navy);
      }

      .treasurercommunity-widget {
        padding: 20px;
        margin-bottom: 16px;
      }

      .treasurercommunity-widget-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
      }

      .treasurercommunity-widget-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurercommunity-widget-link {
        font-size: 12px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurercommunity-widget-link:hover {
        color: var(--navy-mid);
        text-decoration: underline;
      }

      .treasurercommunity-announcement-item,
      .treasurercommunity-moderation-row,
      .treasurercommunity-staff-row {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: var(--soft-bg);
        padding: 13px;
        margin-bottom: 10px;
        transition:
          background 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease;
      }

      .treasurercommunity-announcement-item:hover,
      .treasurercommunity-moderation-row:hover,
      .treasurercommunity-staff-row:hover {
        background: #ffffff;
        border-color: var(--navy);
        transform: translateY(-1px);
      }

      .treasurercommunity-announcement-label {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        background: var(--green-bg);
        color: var(--green-text);
        font-size: 10px;
        font-weight: 800;
        margin-bottom: 8px;
      }

      .treasurercommunity-announcement-title {
        display: block;
        font-size: 13px;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 4px;
      }

      .treasurercommunity-announcement-text {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.45;
        margin: 0;
      }

      .treasurercommunity-moderation-row,
      .treasurercommunity-staff-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
      }

      .treasurercommunity-moderation-label,
      .treasurercommunity-staff-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
      }

      .treasurercommunity-moderation-count {
        font-size: 16px;
        font-weight: 800;
        color: var(--navy);
      }

      .treasurercommunity-moderation-count.warning {
        color: var(--red-text);
      }

      .treasurercommunity-online-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: #22c55e;
        flex-shrink: 0;
      }

      .treasurercommunity-staff-left {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .treasurercommunity-modal-content {
        border: none;
        border-radius: 28px;
        overflow: hidden;
      }

      .treasurercommunity-modal-header {
        border-bottom: 1px solid var(--border);
      }

      .treasurercommunity-modal-textarea {
        width: 100%;
        min-height: 150px;
        border: 1px solid var(--border);
        border-radius: 22px;
        background: var(--soft-bg);
        resize: none;
        outline: none;
        padding: 14px;
        font-size: 14px;
        color: var(--text);
      }

      .treasurercommunity-modal-textarea:focus {
        background: #ffffff;
        border-color: var(--navy);
      }

      @media (max-width: 992px) {
        .treasurercommunity-widgets-column {
          margin-top: 16px;
        }

        .treasurercommunity-page-head {
          flex-direction: column;
          align-items: flex-start;
        }

        .treasurercommunity-page-actions {
          width: 100%;
        }
      }

      @media (max-width: 780px) {
        .treasurercommunity-sidebar {
          width: var(--sidebar-mini);
        }

        .treasurercommunity-main {
          margin-left: var(--sidebar-mini);
        }

        .treasurercommunity-toggle-wrap {
          left: var(--sidebar-mini);
        }

        .treasurercommunity-identity-name,
        .treasurercommunity-identity-chip,
        .treasurercommunity-menu-label,
        .treasurercommunity-menu-count {
          opacity: 0;
          width: 0;
          pointer-events: none;
        }

        .treasurercommunity-body {
          padding: 18px 14px 36px;
        }

        .treasurercommunity-topbar {
          padding: 0 14px;
        }

        .treasurercommunity-profile-name,
        .treasurercommunity-profile-role {
          display: none;
        }

        .treasurercommunity-topbar-search {
          max-width: none;
        }

        .treasurercommunity-post-gallery {
          grid-template-columns: 1fr;
        }

        .treasurercommunity-post-actions {
          grid-template-columns: 1fr;
        }

        .treasurercommunity-page-actions .treasurercommunity-btn {
          width: 100%;
        }

        .treasurercommunity-comment-box {
          align-items: stretch;
        }

        .treasurercommunity-comment-box .treasurercommunity-btn {
          min-width: 70px;
        }
      }
      .treasurercommunity-toggle-btn,
      .treasurercommunity-menu-link,
      .treasurercommunity-logout,
      .treasurercommunity-topbar-search,
      .treasurercommunity-notification-btn,
      .treasurercommunity-profile,
      .treasurercommunity-page-head,
      .treasurercommunity-card,
      .treasurercommunity-btn,
      .treasurercommunity-tool-btn,
      .treasurercommunity-action-btn,
      .treasurercommunity-post-menu-btn,
      .treasurercommunity-composer-input,
      .treasurercommunity-post-photo,
      .treasurercommunity-comment-input,
      .treasurercommunity-announcement-item,
      .treasurercommunity-moderation-row,
      .treasurercommunity-staff-row,
      .treasurercommunity-dropdown-item,
      .treasurercommunity-modal-textarea {
        transition:
          background 0.18s ease,
          color 0.18s ease,
          border-color 0.18s ease,
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-toggle-btn:hover {
        transform: scale(1.05);
      }

      .treasurercommunity-menu-link:hover,
      .treasurercommunity-logout:hover {
        transform: translateX(3px);
      }

      .treasurercommunity-topbar-search:focus-within,
      .treasurercommunity-comment-input:focus,
      .treasurercommunity-modal-textarea:focus {
        box-shadow: 0 0 0 3px rgba(5, 22, 80, 0.08);
      }

      .treasurercommunity-notification-btn:hover,
      .treasurercommunity-profile:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.08);
      }

      .treasurercommunity-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
        border-color: #d7deea;
      }

      .treasurercommunity-composer-input:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.06);
      }

      .treasurercommunity-btn:hover,
      .treasurercommunity-tool-btn:hover,
      .treasurercommunity-action-btn:hover,
      .treasurercommunity-post-menu-btn:hover {
        transform: translateY(-1px);
      }

      .treasurercommunity-btn-navy:hover {
        background: var(--lime);
        border-color: var(--lime);
        color: var(--navy);
        box-shadow: 0 8px 16px rgba(204, 255, 0, 0.18);
      }

      .treasurercommunity-btn-soft:hover,
      .treasurercommunity-tool-btn:hover,
      .treasurercommunity-action-btn:hover,
      .treasurercommunity-post-menu-btn:hover {
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.08);
      }

      .treasurercommunity-post:hover .treasurercommunity-post-avatar,
      .treasurercommunity-composer:hover .treasurercommunity-composer-avatar {
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(5, 22, 80, 0.12);
      }

      .treasurercommunity-post-avatar,
      .treasurercommunity-composer-avatar,
      .treasurercommunity-comment-avatar,
      .treasurercommunity-avatar {
        transition:
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-post-photo:hover {
        transform: scale(1.015);
        border-color: var(--navy);
        box-shadow: 0 10px 22px rgba(5, 22, 80, 0.08);
      }

      .treasurercommunity-action-btn:hover {
        background: var(--navy);
        color: #ffffff;
      }

      .treasurercommunity-action-btn.flag:hover {
        background: var(--red-text);
        color: #ffffff;
      }

      .treasurercommunity-post-menu-btn:hover {
        background: var(--navy);
        color: #ffffff;
        border-color: var(--navy);
      }

      .treasurercommunity-dropdown-item:hover {
        transform: translateX(2px);
      }

      .treasurercommunity-dropdown-item.flag:hover {
        background: var(--red-bg);
        color: var(--red-text);
      }

      .treasurercommunity-announcement-item:hover,
      .treasurercommunity-moderation-row:hover,
      .treasurercommunity-staff-row:hover {
        background: #ffffff;
        border-color: #d7deea;
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(5, 22, 80, 0.08);
      }

      .treasurercommunity-online-dot {
        transition:
          transform 0.18s ease,
          box-shadow 0.18s ease;
      }

      .treasurercommunity-staff-row:hover .treasurercommunity-online-dot {
        transform: scale(1.15);
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
      }

      .treasurercommunity-comment-input:focus {
        background: #ffffff;
        border-color: var(--navy);
      }

      .treasurercommunity-modal-textarea:focus {
        background: #ffffff;
        border-color: var(--navy);
      }

      .treasurercommunity-btn:active,
      .treasurercommunity-tool-btn:active,
      .treasurercommunity-action-btn:active,
      .treasurercommunity-post-menu-btn:active {
        transform: translateY(0);
        box-shadow: none;
      }
    </style>
  </head>

  <body>
    <div
      class="treasurercommunity-toggle-wrap"
      id="treasurercommunity-toggle-wrap"
    >
      <button
        class="treasurercommunity-toggle-btn"
        id="treasurercommunity-toggle-button"
        title="Collapse / Expand"
      >
        <i class="fa-solid fa-chevron-left"></i>
      </button>
    </div>

    <div class="treasurercommunity-page">
      <aside class="treasurercommunity-sidebar" id="treasurercommunity-sidebar">
        <div class="treasurercommunity-identity">
          <div class="treasurercommunity-identity-logo">
            <img src="alapan.png" alt="Alapan logo" />
          </div>

          <div>
            <div class="treasurercommunity-identity-name">BarangayKonek</div>
            <span class="treasurercommunity-identity-chip">
              Treasurer Portal
            </span>
          </div>
        </div>

        <nav class="treasurercommunity-menu">
          <a
            href="treasurerdashboard.html"
            class="treasurercommunity-menu-link"
          >
            <div class="treasurercommunity-menu-left">
              <i class="fa-solid fa-house"></i>
              <span class="treasurercommunity-menu-label">Dashboard</span>
            </div>
          </a>

          <a
            href="treasurertransaction.html"
            class="treasurercommunity-menu-link"
          >
            <div class="treasurercommunity-menu-left">
              <i class="fa-solid fa-coins"></i>
              <span class="treasurercommunity-menu-label">Transactions</span>
            </div>

            <span class="treasurercommunity-menu-count">8</span>
          </a>

          <a href="treasurerhistory.html" class="treasurercommunity-menu-link">
            <div class="treasurercommunity-menu-left">
              <i class="fa-solid fa-clock-rotate-left"></i>
              <span class="treasurercommunity-menu-label"
                >Transaction History</span
              >
            </div>
          </a>

          <a
            href="treasurercommunity.html"
            class="treasurercommunity-menu-link active"
          >
            <div class="treasurercommunity-menu-left">
              <i class="fa-solid fa-people-group"></i>
              <span class="treasurercommunity-menu-label">Community</span>
            </div>
          </a>

          <div class="treasurercommunity-menu-divider"></div>

          <a href="treasurersettings.html" class="treasurercommunity-menu-link">
            <div class="treasurercommunity-menu-left">
              <i class="fa-solid fa-gear"></i>
              <span class="treasurercommunity-menu-label">Settings</span>
            </div>
          </a>
        </nav>

        <div class="treasurercommunity-sidebar-footer">
          <a href="home.html" class="treasurercommunity-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="treasurercommunity-menu-label">Log Out</span>
          </a>
        </div>
      </aside>

      <main class="treasurercommunity-main" id="treasurercommunity-main">
        <header class="treasurercommunity-topbar">
          <div class="treasurercommunity-topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              placeholder="Search posts, residents, or financial announcements..."
            />
          </div>

          <div class="treasurercommunity-topbar-right">
            <div class="treasurercommunity-notification-btn">
              <i class="fa-solid fa-bell"></i>
              <span class="treasurercommunity-notification-count">2</span>
            </div>

            <div class="treasurercommunity-profile">
              <div class="treasurercommunity-avatar">LR</div>

              <div>
                <div class="treasurercommunity-profile-name">Luz Reyes</div>
                <div class="treasurercommunity-profile-role">Treasurer</div>
              </div>
            </div>
          </div>
        </header>

        <div class="treasurercommunity-body">
          <div class="row g-3">
            <div class="col-lg-8 col-xl-9">
              <section
                class="treasurercommunity-card treasurercommunity-composer"
              >
                <div class="treasurercommunity-composer-top">
                  <div class="treasurercommunity-composer-avatar">LR</div>

                  <div
                    class="treasurercommunity-composer-input"
                    data-bs-toggle="modal"
                    data-bs-target="#treasurercommunityPostModal"
                  >
                    Share a financial update or announcement with the
                    community...
                  </div>
                </div>

                <div class="treasurercommunity-composer-actions">
                  <div class="d-flex flex-wrap gap-2">
                    <button
                      class="treasurercommunity-tool-btn"
                      data-bs-toggle="modal"
                      data-bs-target="#treasurercommunityPostModal"
                    >
                      <i class="fa-regular fa-image"></i>
                      Photo
                    </button>

                    <button
                      class="treasurercommunity-tool-btn"
                      data-bs-toggle="modal"
                      data-bs-target="#treasurercommunityPostModal"
                    >
                      <i class="fa-solid fa-paperclip"></i>
                      File
                    </button>
                  </div>

                  <button
                    class="treasurercommunity-btn treasurercommunity-btn-navy"
                    data-bs-toggle="modal"
                    data-bs-target="#treasurercommunityPostModal"
                  >
                    Post Financial Update
                  </button>
                </div>
              </section>

              <article class="treasurercommunity-card treasurercommunity-post">
                <div class="treasurercommunity-post-head">
                  <div class="treasurercommunity-post-user">
                    <div class="treasurercommunity-post-avatar">LR</div>

                    <div>
                      <span class="treasurercommunity-post-name">
                        Luz Reyes
                      </span>
                      <span class="treasurercommunity-post-meta">
                        Treasurer · Apr 14, 2026 · 9:30 AM
                      </span>
                    </div>
                  </div>

                  <div class="treasurercommunity-post-options">
                    <span class="treasurercommunity-post-tag">Official</span>

                    <div class="dropdown">
                      <button
                        class="treasurercommunity-post-menu-btn"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        <i class="fa-solid fa-ellipsis"></i>
                      </button>

                      <ul
                        class="dropdown-menu dropdown-menu-end treasurercommunity-dropdown-menu"
                      >
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item"
                            href="#"
                          >
                            <i class="fa-solid fa-eye"></i>
                            View Details
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item"
                            href="#"
                          >
                            <i class="fa-solid fa-pen"></i>
                            Edit Post
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item flag"
                            href="#"
                          >
                            <i class="fa-solid fa-flag"></i>
                            Flag Post
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <p class="treasurercommunity-post-text">
                  📢 Reminder: Payment cut-off for document requests is on
                  <strong>April 20, 2026</strong> at 3:00 PM. Residents with
                  pending document service payments are encouraged to settle
                  before release. Official receipts will be issued at the
                  Treasurer desk.
                </p>

                <div class="treasurercommunity-post-summary">
                  <span>42 reactions</span>
                  <span>12 comments</span>
                </div>

                <div class="treasurercommunity-post-actions">
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-regular fa-thumbs-up"></i>
                    Like
                  </button>
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-regular fa-comment"></i>
                    Comment
                  </button>
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-solid fa-share"></i>
                    Share
                  </button>
                </div>

                <div class="treasurercommunity-comment-box">
                  <div class="treasurercommunity-comment-avatar">LR</div>
                  <input
                    class="treasurercommunity-comment-input"
                    type="text"
                    placeholder="Write a comment..."
                  />
                  <button
                    class="treasurercommunity-btn treasurercommunity-btn-navy"
                  >
                    Send
                  </button>
                </div>
              </article>

              <article class="treasurercommunity-card treasurercommunity-post">
                <div class="treasurercommunity-post-head">
                  <div class="treasurercommunity-post-user">
                    <div class="treasurercommunity-post-avatar">AL</div>

                    <div>
                      <span class="treasurercommunity-post-name">
                        Ana Lopez
                      </span>
                      <span class="treasurercommunity-post-meta">
                        Resident · Apr 13, 2026 · 5:15 PM
                      </span>
                    </div>
                  </div>

                  <div class="treasurercommunity-post-options">
                    <span class="treasurercommunity-post-tag resident">
                      Resident Post
                    </span>

                    <div class="dropdown">
                      <button
                        class="treasurercommunity-post-menu-btn"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        <i class="fa-solid fa-ellipsis"></i>
                      </button>

                      <ul
                        class="dropdown-menu dropdown-menu-end treasurercommunity-dropdown-menu"
                      >
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item"
                            href="#"
                          >
                            <i class="fa-solid fa-eye"></i>
                            View Details
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item"
                            href="#"
                          >
                            <i class="fa-solid fa-eye-slash"></i>
                            Hide Post
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item flag"
                            href="#"
                          >
                            <i class="fa-solid fa-flag"></i>
                            Flag Post
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <p class="treasurercommunity-post-text">
                  Good afternoon po. May update po ba kung kailan puwede kunin
                  ang official receipt for my barangay clearance payment?
                  Salamat po.
                </p>

                <div class="treasurercommunity-post-summary">
                  <span>18 reactions</span>
                  <span>5 comments</span>
                </div>

                <div class="treasurercommunity-post-actions">
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-regular fa-thumbs-up"></i>
                    Like
                  </button>
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-regular fa-comment"></i>
                    Comment
                  </button>
                  <button class="treasurercommunity-action-btn flag">
                    <i class="fa-solid fa-flag"></i>
                    Flag
                  </button>
                </div>
              </article>

              <article class="treasurercommunity-card treasurercommunity-post">
                <div class="treasurercommunity-post-head">
                  <div class="treasurercommunity-post-user">
                    <div class="treasurercommunity-post-avatar">RC</div>

                    <div>
                      <span class="treasurercommunity-post-name">
                        Ramon Cruz
                      </span>
                      <span class="treasurercommunity-post-meta">
                        Barangay Captain · Apr 12, 2026 · 8:40 AM
                      </span>
                    </div>
                  </div>

                  <div class="treasurercommunity-post-options">
                    <span class="treasurercommunity-post-tag">Official</span>

                    <div class="dropdown">
                      <button
                        class="treasurercommunity-post-menu-btn"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        <i class="fa-solid fa-ellipsis"></i>
                      </button>

                      <ul
                        class="dropdown-menu dropdown-menu-end treasurercommunity-dropdown-menu"
                      >
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item"
                            href="#"
                          >
                            <i class="fa-solid fa-eye"></i>
                            View Details
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item"
                            href="#"
                          >
                            <i class="fa-solid fa-pen"></i>
                            Edit Post
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item treasurercommunity-dropdown-item flag"
                            href="#"
                          >
                            <i class="fa-solid fa-flag"></i>
                            Flag Post
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <p class="treasurercommunity-post-text">
                  The Treasurer office will post the updated list of paid and
                  pending document service transactions this week. Please keep
                  your transaction reference for easier verification.
                </p>

                <div class="treasurercommunity-post-gallery">
                  <div class="treasurercommunity-post-photo">
                    Announcement Image
                  </div>
                  <div class="treasurercommunity-post-photo">
                    Schedule Preview
                  </div>
                </div>

                <div class="treasurercommunity-post-summary">
                  <span>31 reactions</span>
                  <span>6 comments</span>
                </div>

                <div class="treasurercommunity-post-actions">
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-regular fa-thumbs-up"></i>
                    Like
                  </button>
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-regular fa-comment"></i>
                    Comment
                  </button>
                  <button class="treasurercommunity-action-btn">
                    <i class="fa-solid fa-share"></i>
                    Share
                  </button>
                </div>
              </article>

              <div class="text-center mt-3">
                <button
                  class="treasurercommunity-btn treasurercommunity-btn-navy"
                >
                  <i class="fa-solid fa-chevron-down"></i>
                  Load More Posts
                </button>
              </div>
            </div>

            <aside class="col-lg-4 col-xl-3 treasurercommunity-widgets-column">
              <section
                class="treasurercommunity-card treasurercommunity-widget"
              >
                <div class="treasurercommunity-widget-head">
                  <h3 class="treasurercommunity-widget-title">
                    Latest Announcements
                  </h3>
                  <a href="#" class="treasurercommunity-widget-link">Manage</a>
                </div>

                <div class="treasurercommunity-announcement-item">
                  <span class="treasurercommunity-announcement-label">
                    Official
                  </span>
                  <span class="treasurercommunity-announcement-title">
                    April Collection Update
                  </span>
                  <p class="treasurercommunity-announcement-text">
                    Document service collections for April are being reconciled.
                    Residents with pending payments may settle during office
                    hours.
                  </p>
                </div>

                <div class="treasurercommunity-announcement-item">
                  <span class="treasurercommunity-announcement-label">
                    Official
                  </span>
                  <span class="treasurercommunity-announcement-title">
                    Official Receipts
                  </span>
                  <p class="treasurercommunity-announcement-text">
                    Official receipts are available after payment verification
                    and document release confirmation.
                  </p>
                </div>

                <button
                  class="treasurercommunity-btn treasurercommunity-btn-navy w-100"
                >
                  <i class="fa-solid fa-plus"></i>
                  Post Financial Notice
                </button>
              </section>

              <section
                class="treasurercommunity-card treasurercommunity-widget"
              >
                <div class="treasurercommunity-widget-head">
                  <h3 class="treasurercommunity-widget-title">Payment Watch</h3>
                  <a href="#" class="treasurercommunity-widget-link"
                    >View All</a
                  >
                </div>

                <div class="treasurercommunity-moderation-row">
                  <span class="treasurercommunity-moderation-label">
                    Payment Questions
                  </span>
                  <span class="treasurercommunity-moderation-count">12</span>
                </div>

                <div class="treasurercommunity-moderation-row">
                  <span class="treasurercommunity-moderation-label">
                    Flagged Posts
                  </span>
                  <span class="treasurercommunity-moderation-count warning">
                    2
                  </span>
                </div>

                <div class="treasurercommunity-moderation-row">
                  <span class="treasurercommunity-moderation-label">
                    Active Notices
                  </span>
                  <span class="treasurercommunity-moderation-count">84</span>
                </div>
              </section>

              <section
                class="treasurercommunity-card treasurercommunity-widget"
              >
                <div class="treasurercommunity-widget-head">
                  <h3 class="treasurercommunity-widget-title">Online Staff</h3>
                </div>

                <div class="treasurercommunity-staff-row">
                  <div class="treasurercommunity-staff-left">
                    <span class="treasurercommunity-online-dot"></span>
                    <span class="treasurercommunity-staff-name">
                      Barangay Captain
                    </span>
                  </div>
                </div>

                <div class="treasurercommunity-staff-row">
                  <div class="treasurercommunity-staff-left">
                    <span class="treasurercommunity-online-dot"></span>
                    <span class="treasurercommunity-staff-name">
                      Treasurer
                    </span>
                  </div>
                </div>

                <div class="treasurercommunity-staff-row">
                  <div class="treasurercommunity-staff-left">
                    <span class="treasurercommunity-online-dot"></span>
                    <span class="treasurercommunity-staff-name">
                      Treasurer
                    </span>
                  </div>
                </div>
              </section>
            </aside>
          </div>
        </div>
      </main>
    </div>

    <div
      class="modal fade"
      id="treasurercommunityPostModal"
      tabindex="-1"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content treasurercommunity-modal-content">
          <div class="modal-header treasurercommunity-modal-header">
            <h5 class="modal-title fw-bold text-primary-emphasis">
              Create Community Post
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
            ></button>
          </div>

          <div class="modal-body">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="treasurercommunity-avatar">LR</div>
              <div>
                <div class="fw-bold text-primary-emphasis">Luz Reyes</div>
                <small class="text-muted">Posting as Treasurer</small>
              </div>
            </div>

            <textarea
              class="treasurercommunity-modal-textarea"
              placeholder="Share a payment reminder, collection update, or official financial announcement..."
            ></textarea>

            <div class="form-check mt-3">
              <input
                class="form-check-input"
                type="checkbox"
                id="treasurercommunityAnnouncementCheck"
              />
              <label
                class="form-check-label fw-semibold"
                for="treasurercommunityAnnouncementCheck"
              >
                Post as official announcement
              </label>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="treasurercommunity-btn treasurercommunity-btn-soft"
              data-bs-dismiss="modal"
            >
              Cancel
            </button>

            <button
              type="button"
              class="treasurercommunity-btn treasurercommunity-btn-navy"
            >
              <i class="fa-solid fa-paper-plane"></i>
              Post
            </button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      var treasurercommunitySidebar = document.getElementById(
        "treasurercommunity-sidebar",
      );

      var treasurercommunityMainArea = document.getElementById(
        "treasurercommunity-main",
      );

      var treasurercommunityToggleWrap = document.getElementById(
        "treasurercommunity-toggle-wrap",
      );

      var treasurercommunityToggleButton = document.getElementById(
        "treasurercommunity-toggle-button",
      );

      treasurercommunityToggleButton.addEventListener("click", function () {
        var treasurercommunityIsSidebarMini =
          treasurercommunitySidebar.classList.toggle("mini");

        treasurercommunityMainArea.classList.toggle(
          "shifted",
          treasurercommunityIsSidebarMini,
        );

        treasurercommunityToggleWrap.classList.toggle(
          "mini",
          treasurercommunityIsSidebarMini,
        );
      });
    </script>
  </body>
</html>
