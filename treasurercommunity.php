<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Communityk</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <!-- Font Awesome -->
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      rel="stylesheet"
    />
    <!-- DM Sans -->
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
      :root {
        --bs-body-font-family: "DM Sans", sans-serif;
        --bs-body-color: #1a2240;
        --bs-body-bg: #f2f4f8;
        --treasurercommunity-navy: #051650;
        --treasurercommunity-navy-mid: #0a2160;
        --treasurercommunity-lime: #ccff00;
        --treasurercommunity-lime-dim: #aadd00;
        --treasurercommunity-border: #e3e7f0;
        --treasurercommunity-surface: #ffffff;
        --treasurercommunity-text-soft: #7a869a;
        --treasurercommunity-sidebar-width: 228px;
        --treasurercommunity-topbar-height: 62px;
      }

      body {
        font-family: "DM Sans", sans-serif;
        background: #f2f4f8;
        color: #1a2240;
      }

      /* ── ANIMATIONS ── */
      @keyframes slide-from-left {
        from {
          opacity: 0;
          transform: translateX(-20px);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }
      @keyframes drop-from-top {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      @keyframes rise-from-bottom {
        from {
          opacity: 0;
          transform: translateY(14px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* ── SIDEBAR STRUCTURE ── */
      .treasurercommunity-sidebar {
        width: var(--treasurercommunity-sidebar-width);
        background: var(--treasurercommunity-navy);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1040;
        display: flex;
        flex-direction: column;
        animation: slide-from-left 0.4s cubic-bezier(0.22, 0.68, 0, 1.1) both;
      }

      .treasurercommunity-brand-row {
        height: var(--treasurercommunity-topbar-height);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
      }

      .treasurercommunity-brand-logo {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--treasurercommunity-lime);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .treasurercommunity-brand-logo i {
        font-size: 13px;
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-brand-name {
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
      }
      .treasurercommunity-brand-sub {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.4);
      }

      .treasurercommunity-nav-area {
        flex: 1;
        padding: 14px 10px;
        overflow-y: auto;
      }

      .treasurercommunity-nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.07);
        margin: 7px 8px;
      }

      .treasurercommunity-nav-link {
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

      .treasurercommunity-nav-link:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
      }

      .treasurercommunity-nav-link.active {
        background: var(--treasurercommunity-lime);
        color: var(--treasurercommunity-navy);
        font-weight: 700;
      }

      .treasurercommunity-nav-badge {
        font-size: 10px;
        font-weight: 800;
        background: rgba(255, 255, 255, 0.13);
        color: rgba(255, 255, 255, 0.8);
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }

      .treasurercommunity-nav-link.active .treasurercommunity-nav-badge {
        background: rgba(5, 22, 80, 0.18);
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-sidebar-footer {
        padding: 12px 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.07);
      }

      .treasurercommunity-logout-btn {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 13px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.4);
        text-decoration: none;
        transition: all 0.15s;
      }

      .treasurercommunity-logout-btn:hover {
        background: rgba(220, 38, 38, 0.12);
        color: #fca5a5;
      }

      /* ── MAIN WRAPPER ── */
      .treasurercommunity-main {
        margin-left: var(--treasurercommunity-sidebar-width);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      /* ── TOPBAR ── */
      .treasurercommunity-topbar {
        height: var(--treasurercommunity-topbar-height);
        background: var(--treasurercommunity-surface);
        border-bottom: 1px solid var(--treasurercommunity-border);
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 0 24px;
        position: sticky;
        top: 0;
        z-index: 30;
        animation: drop-from-top 0.36s ease both;
      }

      .treasurercommunity-topbar-logo {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--treasurercommunity-navy);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .treasurercommunity-topbar-logo i {
        font-size: 12px;
        color: var(--treasurercommunity-lime);
      }

      .treasurercommunity-search-wrap {
        flex: 1;
        max-width: 380px;
        height: 36px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        background: #f2f4f8;
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 10px;
        transition: border-color 0.18s;
      }

      .treasurercommunity-search-wrap:focus-within {
        border-color: var(--treasurercommunity-navy);
        background: #fff;
      }

      .treasurercommunity-search-wrap i {
        font-size: 12px;
        color: var(--treasurercommunity-text-soft);
        flex-shrink: 0;
      }

      .treasurercommunity-search-wrap input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-family: inherit;
        font-size: 13px;
        color: #1a2240;
      }

      .treasurercommunity-search-wrap input::placeholder {
        color: var(--treasurercommunity-text-soft);
      }

      .treasurercommunity-notif-btn {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: #f2f4f8;
        border: 1px solid var(--treasurercommunity-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: var(--treasurercommunity-text-soft);
        text-decoration: none;
        position: relative;
        transition: all 0.15s;
        cursor: pointer;
      }

      .treasurercommunity-notif-btn:hover {
        border-color: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-notif-bubble {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 7px;
        height: 7px;
        background: #22c55e;
        border-radius: 50%;
        border: 1.5px solid #fff;
      }

      .treasurercommunity-profile-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px 4px 5px;
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 10px;
        background: #f2f4f8;
        cursor: pointer;
        transition: border-color 0.15s;
        text-decoration: none;
      }

      .treasurercommunity-profile-chip:hover {
        border-color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-avatar-small {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: var(--treasurercommunity-navy);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        color: var(--treasurercommunity-lime);
        flex-shrink: 0;
      }

      .treasurercommunity-staff-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--treasurercommunity-navy);
        white-space: nowrap;
      }
      .treasurercommunity-staff-role {
        font-size: 11px;
        color: var(--treasurercommunity-text-soft);
        line-height: 1;
      }

      /* ── BODY AREA ── */
      .treasurercommunity-body {
        padding: 22px 24px 48px;
        flex: 1;
      }

      /* ── COMPOSER BOX ── */
      .treasurercommunity-composer {
        background: var(--treasurercommunity-surface);
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 14px;
        padding: 16px 18px;
        animation: rise-from-bottom 0.4s 0.14s ease both;
      }

      .treasurercommunity-composer-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--treasurercommunity-navy);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: var(--treasurercommunity-lime);
        border: 2px solid var(--treasurercommunity-lime);
        flex-shrink: 0;
      }

      .treasurercommunity-compose-field {
        flex: 1;
        background: #f2f4f8;
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 999px;
        padding: 10px 18px;
        font-size: 14px;
        color: var(--treasurercommunity-text-soft);
        cursor: pointer;
        transition: border-color 0.15s;
        font-family: inherit;
      }

      .treasurercommunity-compose-field:hover {
        border-color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-compose-tool-btn {
        height: 32px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid var(--treasurercommunity-border);
        background: var(--treasurercommunity-surface);
        color: var(--treasurercommunity-text-soft);
        font-size: 12px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.14s;
      }

      .treasurercommunity-compose-tool-btn:hover {
        background: rgba(5, 22, 80, 0.04);
        border-color: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-ann-toggle {
        font-size: 12px;
        font-weight: 600;
        color: var(--treasurercommunity-text-soft);
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 6px;
      }

      /* ── POST ITEM ── */
      .treasurercommunity-post-item {
        background: var(--treasurercommunity-surface);
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 14px;
        overflow: hidden;
        transition: box-shadow 0.18s;
      }

      .treasurercommunity-post-item:hover {
        box-shadow: 0 4px 14px rgba(5, 22, 80, 0.1);
      }

      .treasurercommunity-post-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(5, 22, 80, 0.07);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        color: var(--treasurercommunity-navy);
        flex-shrink: 0;
      }

      .treasurercommunity-post-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a2240;
        display: block;
      }

      .treasurercommunity-post-time {
        font-size: 11px;
        color: var(--treasurercommunity-text-soft);
      }

      .treasurercommunity-role-pill {
        display: inline-flex;
        align-items: center;
        height: 20px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2px;
      }

      .treasurercommunity-pill-official {
        background: var(--treasurercommunity-lime);
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-pill-resident {
        background: rgba(5, 22, 80, 0.07);
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-post-body-text {
        font-size: 14px;
        line-height: 1.65;
        color: #1a2240;
      }

      .treasurercommunity-options-btn {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        border: none;
        background: transparent;
        color: var(--treasurercommunity-text-soft);
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.14s;
      }

      .treasurercommunity-options-btn:hover {
        background: rgba(5, 22, 80, 0.06);
        color: var(--treasurercommunity-navy);
      }

      /* reaction bar */
      .treasurercommunity-reaction-bar {
        border-top: 1px solid var(--treasurercommunity-border);
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 12.5px;
        color: var(--treasurercommunity-text-soft);
        flex-wrap: wrap;
      }

      .treasurercommunity-action-row {
        display: flex;
        border-top: 1px solid var(--treasurercommunity-border);
      }

      .treasurercommunity-action-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 10px 0;
        border: none;
        background: none;
        cursor: pointer;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        color: var(--treasurercommunity-text-soft);
        transition:
          background 0.14s,
          color 0.14s;
      }

      .treasurercommunity-action-btn:hover {
        background: rgba(5, 22, 80, 0.04);
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-action-divider {
        width: 1px;
        background: var(--treasurercommunity-border);
        margin: 6px 0;
      }

      /* comment section */
      .treasurercommunity-comment-section {
        border-top: 1px solid var(--treasurercommunity-border);
        display: none;
      }
      .treasurercommunity-comment-section.open {
        display: block;
      }

      .treasurercommunity-comment-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(5, 22, 80, 0.07);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        color: var(--treasurercommunity-navy);
        border: 2px solid var(--treasurercommunity-lime);
        flex-shrink: 0;
      }

      .treasurercommunity-comment-bubble {
        background: #f2f4f8;
        border-radius: 10px;
        padding: 8px 12px;
        flex: 1;
      }

      .treasurercommunity-comment-author {
        font-size: 12px;
        font-weight: 700;
        color: var(--treasurercommunity-navy);
      }
      .treasurercommunity-comment-text {
        font-size: 13px;
        color: #555;
        line-height: 1.45;
      }

      .treasurercommunity-comment-input {
        flex: 1;
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 20px;
        padding: 8px 14px;
        font-size: 13px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s;
        background: var(--treasurercommunity-surface);
      }

      .treasurercommunity-comment-input:focus {
        border-color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-send-btn {
        background: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-lime);
        border: none;
        border-radius: 20px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.14s;
      }

      .treasurercommunity-send-btn:hover {
        background: var(--treasurercommunity-navy-mid);
      }

      /* ── WIDGET PANELS ── */
      .treasurercommunity-widget {
        background: var(--treasurercommunity-surface);
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 14px;
        overflow: hidden;
      }

      .treasurercommunity-widget-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 13px 16px;
        border-bottom: 1px solid var(--treasurercommunity-border);
      }

      .treasurercommunity-widget-head h5 {
        font-size: 13px;
        font-weight: 700;
        color: var(--treasurercommunity-navy);
        margin: 0;
      }

      .treasurercommunity-widget-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--treasurercommunity-text-soft);
        text-decoration: none;
      }
      .treasurercommunity-widget-link:hover {
        color: var(--treasurercommunity-navy);
      }

      .treasurercommunity-ann-item {
        padding: 12px 16px;
        border-bottom: 1px solid var(--treasurercommunity-border);
      }
      .treasurercommunity-ann-item:last-child {
        border-bottom: none;
      }

      .treasurercommunity-ann-badge {
        display: inline-flex;
        align-items: center;
        height: 18px;
        padding: 0 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        background: var(--treasurercommunity-lime);
        color: var(--treasurercommunity-navy);
        margin-bottom: 5px;
      }

      .treasurercommunity-ann-title {
        font-size: 13px;
        font-weight: 700;
        color: #1a2240;
        display: block;
        margin-bottom: 3px;
      }
      .treasurercommunity-ann-preview {
        font-size: 12px;
        color: var(--treasurercommunity-text-soft);
        line-height: 1.45;
      }

      .treasurercommunity-post-ann-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        margin: 0 16px 14px;
        min-height: 34px;
        border-radius: 9px;
        border: none;
        background: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-lime);
        font-size: 12px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.14s;
        width: calc(100% - 32px);
      }

      .treasurercommunity-post-ann-btn:hover {
        background: var(--treasurercommunity-navy-mid);
      }

      .treasurercommunity-mod-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 11px 16px;
        border-bottom: 1px solid var(--treasurercommunity-border);
        font-size: 13px;
      }

      .treasurercommunity-mod-row:last-child {
        border-bottom: none;
      }
      .treasurercommunity-mod-name {
        color: #1a2240;
      }
      .treasurercommunity-mod-count {
        font-weight: 700;
        color: var(--treasurercommunity-navy);
      }
      .treasurercommunity-mod-alert {
        font-weight: 700;
        color: #dc2626;
      }

      .treasurercommunity-contact-row {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 10px 16px;
        border-bottom: 1px solid var(--treasurercommunity-border);
        font-size: 13px;
        color: #1a2240;
      }

      .treasurercommunity-contact-row:last-child {
        border-bottom: none;
      }

      .treasurercommunity-online-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        flex-shrink: 0;
      }

      /* ── COMPOSE MODAL ── */
      .treasurercommunity-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 900;
        background: rgba(5, 22, 80, 0.62);
        backdrop-filter: blur(3px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
      }

      .treasurercommunity-modal-overlay.open {
        display: flex;
      }

      .treasurercommunity-modal-box {
        background: var(--treasurercommunity-surface);
        border-radius: 16px;
        max-width: 520px;
        width: 100%;
        max-height: 88vh;
        overflow-y: auto;
        box-shadow: 0 16px 60px rgba(5, 22, 80, 0.28);
        border-top: 4px solid var(--treasurercommunity-lime);
      }

      .treasurercommunity-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--treasurercommunity-border);
        position: sticky;
        top: 0;
        background: var(--treasurercommunity-surface);
        z-index: 2;
      }

      .treasurercommunity-modal-header h3 {
        font-size: 15px;
        font-weight: 700;
        color: var(--treasurercommunity-navy);
        margin: 0;
      }

      .treasurercommunity-modal-close {
        background: rgba(5, 22, 80, 0.06);
        border: none;
        color: #555;
        font-size: 15px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
      }

      .treasurercommunity-modal-close:hover {
        background: rgba(5, 22, 80, 0.12);
      }

      .treasurercommunity-modal-body {
        padding: 18px 20px 22px;
      }

      .treasurercommunity-modal-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-lime);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        flex-shrink: 0;
        border: 2px solid var(--treasurercommunity-lime);
      }

      .treasurercommunity-modal-textarea {
        width: 100%;
        min-height: 120px;
        border: none;
        outline: none;
        font-family: inherit;
        font-size: 15px;
        color: #1a2240;
        resize: none;
        background: transparent;
        line-height: 1.6;
      }

      .treasurercommunity-ann-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        margin-top: 10px;
        border: 1px solid var(--treasurercommunity-border);
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--treasurercommunity-text-soft);
        cursor: pointer;
      }

      .treasurercommunity-modal-submit {
        width: 100%;
        margin-top: 12px;
        background: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-lime);
        border: none;
        border-radius: 10px;
        padding: 13px;
        font-size: 14px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background 0.15s;
      }

      .treasurercommunity-modal-submit:hover {
        background: var(--treasurercommunity-navy-mid);
      }

      /* ── LOAD MORE ── */
      .treasurercommunity-load-btn {
        background: var(--treasurercommunity-navy);
        color: var(--treasurercommunity-lime);
        border: none;
        border-radius: 10px;
        padding: 11px 32px;
        font-size: 14px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.15s;
      }

      .treasurercommunity-load-btn:hover {
        background: var(--treasurercommunity-navy-mid);
      }

      /* ── WIDGETS sticky ── */
      .treasurercommunity-widgets-col {
        position: sticky;
        top: calc(var(--treasurercommunity-topbar-height) + 22px);
        max-height: calc(
          100vh - var(--treasurercommunity-topbar-height) - 44px
        );
        overflow-y: auto;
        scrollbar-width: none;
      }

      .treasurercommunity-widgets-col::-webkit-scrollbar {
        display: none;
      }

      /* ── RESPONSIVE ── */
      @media (max-width: 780px) {
        .treasurercommunity-sidebar {
          display: none;
        }
        .treasurercommunity-main {
          margin-left: 0;
        }
      }
    </style>
  </head>
  <body>
    <!-- COMPOSE MODAL -->
    <div
      class="treasurercommunity-modal-overlay"
      id="composeModal"
      onclick="closeComposeOnBg(event)"
    >
      <div class="treasurercommunity-modal-box">
        <div class="treasurercommunity-modal-header">
          <h3>Create Post</h3>
          <button
            class="treasurercommunity-modal-close"
            onclick="closeCompose()"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="treasurercommunity-modal-body">
          <div class="d-flex gap-3 align-items-start mb-3">
            <div class="treasurercommunity-modal-avatar">LR</div>
            <div>
              <div
                class="fw-bold"
                style="font-size: 14px; color: var(--treasurercommunity-navy)"
              >
                Luz Reyes
              </div>
              <div class="text-muted" style="font-size: 12px">
                Posting as Treasurer &middot; Barangay Alapan 1-A
              </div>
            </div>
          </div>
          <textarea
            class="treasurercommunity-modal-textarea"
            id="postTextarea"
            placeholder="Share a financial update or announcement&#x2026;"
          ></textarea>
          <label class="treasurercommunity-ann-row">
            <input
              type="checkbox"
              style="accent-color: var(--treasurercommunity-navy)"
            />
            Post as Official Announcement
          </label>
          <button class="treasurercommunity-modal-submit">
            <i class="fa-solid fa-paper-plane"></i>
            Post
          </button>
        </div>
      </div>
    </div>

    <div class="treasurercommunity-page d-flex">
      <!-- SIDEBAR -->
      <aside class="treasurercommunity-sidebar">
        <div class="treasurercommunity-brand-row">
          <div class="treasurercommunity-brand-logo">
            <i class="fa-solid fa-landmark"></i>
          </div>
          <div>
            <div class="treasurercommunity-brand-name">Alapan 1-A</div>
            <div class="treasurercommunity-brand-sub">BarangayKonek</div>
          </div>
        </div>

        <nav class="treasurercommunity-nav-area d-flex flex-column gap-1">
          <a href="treasuredashboard.html" class="treasurercommunity-nav-link">
            Dashboard
          </a>

          <div class="treasurercommunity-nav-divider"></div>

          <a
            href="treasurercollection.html"
            class="treasurercommunity-nav-link"
          >
            Collections
          </a>
          <a href="treasurerfee.html" class="treasurercommunity-nav-link">
            Fee Records
          </a>
          <a href="treasurerhistory.html" class="treasurercommunity-nav-link">
            Financial Records
          </a>

          <div class="treasurercommunity-nav-divider"></div>

          <a href="treasurercommunity.html" class="treasurercommunity-nav-link">
            Community
          </a>
          <a href="#" class="treasurercommunity-nav-link"> Activity Logs </a>
        </nav>

        <div class="treasurercommunity-sidebar-footer">
          <a href="home.html" class="treasurercommunity-logout-btn">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Logout
          </a>
        </div>
      </aside>

      <!-- MAIN -->
      <div class="treasurercommunity-main flex-fill">
        <!-- TOPBAR -->
        <header class="treasurercommunity-topbar">
          <div class="treasurercommunity-topbar-logo">
            <i class="fa-solid fa-landmark"></i>
          </div>

          <div class="treasurercommunity-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              placeholder="Search posts, residents, announcements&#x2026;"
              id="feedSearch"
            />
          </div>

          <div class="ms-auto d-flex align-items-center gap-2">
            <a href="#" class="treasurercommunity-notif-btn">
              <i class="fa-solid fa-bell"></i>
              <span class="treasurercommunity-notif-bubble"></span>
            </a>
            <div class="treasurercommunity-profile-chip">
              <div class="treasurercommunity-avatar-small">LR</div>
              <div>
                <div class="treasurercommunity-staff-name">Luz Reyes</div>
                <div class="treasurercommunity-staff-role">Treasurer</div>
              </div>
            </div>
          </div>
        </header>

        <!-- BODY -->
        <div class="treasurercommunity-body">
          <div class="row g-4">
            <!-- FEED COLUMN -->
            <div class="col-12 col-lg-8">
              <div
                class="d-flex flex-column gap-3"
                style="animation: rise-from-bottom 0.4s 0.14s ease both"
              >
                <!-- COMPOSER -->
                <div class="treasurercommunity-composer">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="treasurercommunity-composer-avatar">LR</div>
                    <div
                      class="treasurercommunity-compose-field"
                      onclick="openCompose()"
                    >
                      Share a financial update or announcement with the
                      community&#x2026;
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2 pt-2 border-top">
                    <button
                      class="treasurercommunity-compose-tool-btn"
                      onclick="openCompose()"
                    >
                      <i class="fa-regular fa-image"></i> Photo
                    </button>
                    <button
                      class="treasurercommunity-compose-tool-btn"
                      onclick="openCompose()"
                    >
                      <i class="fa-solid fa-paperclip"></i> File
                    </button>
                    <label class="treasurercommunity-ann-toggle ms-auto">
                      <input
                        type="checkbox"
                        style="accent-color: var(--treasurercommunity-navy)"
                      />
                      Post as Announcement
                    </label>
                  </div>
                </div>

                <!-- POST 1 — Official (Treasurer) -->
                <div
                  class="treasurercommunity-post-item"
                  data-poster="luz reyes"
                  data-body="april 2026 collection update fee reminders"
                >
                  <div
                    class="d-flex align-items-start justify-content-between gap-2 p-3 pb-2"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <div class="treasurercommunity-post-avatar">LR</div>
                      <div>
                        <a href="#" class="treasurercommunity-post-name"
                          >Luz Reyes</a
                        >
                        <span class="treasurercommunity-post-time d-block"
                          >Treasurer &middot; Apr 14, 2026 &bull; 10:05 AM</span
                        >
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span
                        class="treasurercommunity-role-pill treasurercommunity-pill-official"
                        >Official</span
                      >
                      <button class="treasurercommunity-options-btn">
                        <i class="fa-solid fa-ellipsis"></i>
                      </button>
                    </div>
                  </div>

                  <div class="px-3 pb-3">
                    <p class="treasurercommunity-post-body-text mb-0">
                      &#x1F4B0;
                      <strong>April 2026 Collection Update:</strong> As of
                      today, we have collected
                      <strong>&#8369;13,100</strong> out of a total billable
                      amount of <strong>&#8369;14,300</strong> &mdash; a
                      <strong>91% collection rate</strong>. Residents with
                      pending fees for Barangay Clearance and Business Permits
                      are kindly reminded to settle at the barangay hall. Thank
                      you for your cooperation!
                    </p>
                  </div>

                  <div class="treasurercommunity-reaction-bar">
                    <span class="d-inline-flex align-items-center gap-1">
                      <i
                        class="fa-solid fa-thumbs-up"
                        style="color: #1877f2; font-size: 14px"
                      ></i>
                      <span>31</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                      <i
                        class="fa-solid fa-heart"
                        style="color: #f33e58; font-size: 14px"
                      ></i>
                      <span>12</span>
                    </span>
                    <span
                      class="ms-auto"
                      style="cursor: pointer"
                      onclick="toggleComments('post1')"
                    >
                      <i class="fa-regular fa-comment"></i> 4 comments
                    </span>
                  </div>

                  <div class="treasurercommunity-action-row">
                    <button class="treasurercommunity-action-btn">
                      <i class="fa-regular fa-thumbs-up"></i> Like
                    </button>
                    <div class="treasurercommunity-action-divider"></div>
                    <button
                      class="treasurercommunity-action-btn"
                      onclick="toggleComments('post1')"
                    >
                      <i class="fa-regular fa-comment"></i> Comment
                    </button>
                  </div>

                  <div
                    class="treasurercommunity-comment-section"
                    id="comments-post1"
                  >
                    <div class="d-flex flex-column gap-2 p-3 pb-2">
                      <div class="d-flex gap-2 align-items-start">
                        <div class="treasurercommunity-comment-avatar">RC</div>
                        <div class="treasurercommunity-comment-bubble">
                          <div class="treasurercommunity-comment-author">
                            Ramon Cruz &middot; Captain
                          </div>
                          <div class="treasurercommunity-comment-text">
                            Thank you for the update, Treasurer Reyes.
                            Let&apos;s follow up with the remaining residents.
                          </div>
                        </div>
                      </div>
                      <div class="d-flex gap-2 align-items-start">
                        <div class="treasurercommunity-comment-avatar">AL</div>
                        <div class="treasurercommunity-comment-bubble">
                          <div class="treasurercommunity-comment-author">
                            Ana Lopez
                          </div>
                          <div class="treasurercommunity-comment-text">
                            Received! We will settle our business permit fee
                            this week po.
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center p-3 pt-1">
                      <div class="treasurercommunity-comment-avatar">LR</div>
                      <input
                        class="treasurercommunity-comment-input"
                        type="text"
                        placeholder="Write a comment&#x2026;"
                      />
                      <button class="treasurercommunity-send-btn">Send</button>
                    </div>
                  </div>
                </div>

                <!-- POST 2 — Resident concern -->
                <div
                  class="treasurercommunity-post-item"
                  data-poster="ryan gomez"
                  data-body="question about business permit fee renewal"
                >
                  <div
                    class="d-flex align-items-start justify-content-between gap-2 p-3 pb-2"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <div class="treasurercommunity-post-avatar">RG</div>
                      <div>
                        <a href="#" class="treasurercommunity-post-name"
                          >Ryan Gomez</a
                        >
                        <span class="treasurercommunity-post-time d-block"
                          >Resident &middot; Apr 13, 2026 &bull; 3:22 PM</span
                        >
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span
                        class="treasurercommunity-role-pill treasurercommunity-pill-resident"
                        >Resident</span
                      >
                      <button class="treasurercommunity-options-btn">
                        <i class="fa-solid fa-ellipsis"></i>
                      </button>
                    </div>
                  </div>

                  <div class="px-3 pb-3">
                    <p class="treasurercommunity-post-body-text mb-0">
                      Magtatanong lang po, kailan po ang deadline ng business
                      permit renewal para sa sari-sari store? At &#8369;200 pa
                      rin po ba ang bayad? Salamat po!
                    </p>
                  </div>

                  <div class="treasurercommunity-reaction-bar">
                    <span class="d-inline-flex align-items-center gap-1">
                      <i
                        class="fa-solid fa-thumbs-up"
                        style="color: #1877f2; font-size: 14px"
                      ></i>
                      <span>8</span>
                    </span>
                    <span
                      class="ms-auto"
                      style="cursor: pointer"
                      onclick="toggleComments('post2')"
                    >
                      <i class="fa-regular fa-comment"></i> 2 comments
                    </span>
                  </div>

                  <div class="treasurercommunity-action-row">
                    <button class="treasurercommunity-action-btn">
                      <i class="fa-regular fa-thumbs-up"></i> Like
                    </button>
                    <div class="treasurercommunity-action-divider"></div>
                    <button
                      class="treasurercommunity-action-btn"
                      onclick="toggleComments('post2')"
                    >
                      <i class="fa-regular fa-comment"></i> Comment
                    </button>
                  </div>

                  <div
                    class="treasurercommunity-comment-section"
                    id="comments-post2"
                  >
                    <div class="d-flex flex-column gap-2 p-3 pb-2">
                      <div class="d-flex gap-2 align-items-start">
                        <div class="treasurercommunity-comment-avatar">LR</div>
                        <div class="treasurercommunity-comment-bubble">
                          <div class="treasurercommunity-comment-author">
                            Luz Reyes &middot; Treasurer
                          </div>
                          <div class="treasurercommunity-comment-text">
                            Magandang hapon! Yes, &#8369;200 pa rin po ang
                            bayad. Ang deadline po ay hanggang April 30, 2026.
                            Pumunta lang po kayo sa barangay hall anytime
                            8AM&#x2013;5PM. Salamat!
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center p-3 pt-1">
                      <div class="treasurercommunity-comment-avatar">LR</div>
                      <input
                        class="treasurercommunity-comment-input"
                        type="text"
                        placeholder="Write a comment&#x2026;"
                      />
                      <button class="treasurercommunity-send-btn">Send</button>
                    </div>
                  </div>
                </div>

                <!-- POST 3 — Captain announcement -->
                <div
                  class="treasurercommunity-post-item"
                  data-poster="ramon cruz"
                  data-body="barangay assembly quarterly budget report"
                >
                  <div
                    class="d-flex align-items-start justify-content-between gap-2 p-3 pb-2"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <div class="treasurercommunity-post-avatar">RC</div>
                      <div>
                        <a href="#" class="treasurercommunity-post-name"
                          >Ramon Cruz</a
                        >
                        <span class="treasurercommunity-post-time d-block"
                          >Barangay Captain &middot; Apr 12, 2026 &bull; 9:00
                          AM</span
                        >
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span
                        class="treasurercommunity-role-pill treasurercommunity-pill-official"
                        >Official</span
                      >
                      <button class="treasurercommunity-options-btn">
                        <i class="fa-solid fa-ellipsis"></i>
                      </button>
                    </div>
                  </div>

                  <div class="px-3 pb-3">
                    <p class="treasurercommunity-post-body-text mb-0">
                      &#x1F4CB; The Q1 2026 budget report has been completed.
                      Total barangay revenue for the quarter was
                      <strong>&#8369;28,460</strong>. We thank our Treasurer for
                      the detailed report. The full breakdown will be presented
                      during the
                      <strong>Barangay Assembly on April 20, 2026</strong>.
                    </p>
                  </div>

                  <div class="treasurercommunity-reaction-bar">
                    <span class="d-inline-flex align-items-center gap-1">
                      <i
                        class="fa-solid fa-thumbs-up"
                        style="color: #1877f2; font-size: 14px"
                      ></i>
                      <span>54</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                      <i
                        class="fa-solid fa-heart"
                        style="color: #f33e58; font-size: 14px"
                      ></i>
                      <span>18</span>
                    </span>
                    <span
                      class="ms-auto"
                      style="cursor: pointer"
                      onclick="toggleComments('post3')"
                    >
                      <i class="fa-regular fa-comment"></i> 6 comments
                    </span>
                  </div>

                  <div class="treasurercommunity-action-row">
                    <button class="treasurercommunity-action-btn">
                      <i class="fa-regular fa-thumbs-up"></i> Like
                    </button>
                    <div class="treasurercommunity-action-divider"></div>
                    <button
                      class="treasurercommunity-action-btn"
                      onclick="toggleComments('post3')"
                    >
                      <i class="fa-regular fa-comment"></i> Comment
                    </button>
                  </div>

                  <div
                    class="treasurercommunity-comment-section"
                    id="comments-post3"
                  >
                    <div class="d-flex flex-column gap-2 p-3 pb-2">
                      <div class="d-flex gap-2 align-items-start">
                        <div class="treasurercommunity-comment-avatar">KM</div>
                        <div class="treasurercommunity-comment-bubble">
                          <div class="treasurercommunity-comment-author">
                            Kristine Mendoza
                          </div>
                          <div class="treasurercommunity-comment-text">
                            Thank you Captain! Excited to hear the full report
                            at the assembly.
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center p-3 pt-1">
                      <div class="treasurercommunity-comment-avatar">LR</div>
                      <input
                        class="treasurercommunity-comment-input"
                        type="text"
                        placeholder="Write a comment&#x2026;"
                      />
                      <button class="treasurercommunity-send-btn">Send</button>
                    </div>
                  </div>
                </div>

                <!-- LOAD MORE -->
                <div class="text-center py-2">
                  <button class="treasurercommunity-load-btn">
                    <i class="fa-solid fa-chevron-down"></i>
                    Load more posts
                  </button>
                </div>
              </div>
            </div>

            <!-- WIDGETS COLUMN -->
            <div class="col-12 col-lg-4">
              <div
                class="d-flex flex-column gap-3 treasurercommunity-widgets-col"
              >
                <!-- Announcements -->
                <div class="treasurercommunity-widget">
                  <div class="treasurercommunity-widget-head">
                    <h5>Latest Announcements</h5>
                    <a href="#" class="treasurercommunity-widget-link"
                      >Manage</a
                    >
                  </div>
                  <div class="treasurercommunity-ann-item">
                    <span class="treasurercommunity-ann-badge">Official</span>
                    <span class="treasurercommunity-ann-title"
                      >April Collection Update</span
                    >
                    <span class="treasurercommunity-ann-preview"
                      >91% collection rate this month. &#8369;1,200 still
                      pending from 8 residents.</span
                    >
                  </div>
                  <div class="treasurercommunity-ann-item">
                    <span class="treasurercommunity-ann-badge">Official</span>
                    <span class="treasurercommunity-ann-title"
                      >Business Permit Deadline</span
                    >
                    <span class="treasurercommunity-ann-preview"
                      >Deadline for business permit renewal is April 30, 2026.
                      Fee: &#8369;200.</span
                    >
                  </div>
                  <div class="treasurercommunity-ann-item">
                    <span class="treasurercommunity-ann-badge">Official</span>
                    <span class="treasurercommunity-ann-title"
                      >Barangay Assembly &#x2014; Apr 20</span
                    >
                    <span class="treasurercommunity-ann-preview"
                      >Q1 2026 budget report will be presented. All residents
                      welcome.</span
                    >
                  </div>
                  <button class="treasurercommunity-post-ann-btn">
                    <i class="fa-solid fa-plus"></i>
                    Post Announcement
                  </button>
                </div>

                <!-- Moderation -->
                <div class="treasurercommunity-widget">
                  <div class="treasurercommunity-widget-head">
                    <h5>Moderation</h5>
                    <a href="#" class="treasurercommunity-widget-link"
                      >View All</a
                    >
                  </div>
                  <div class="treasurercommunity-mod-row">
                    <span class="treasurercommunity-mod-name">Posts Today</span>
                    <span class="treasurercommunity-mod-count">9</span>
                  </div>
                  <div class="treasurercommunity-mod-row">
                    <span class="treasurercommunity-mod-name"
                      >Flagged for Review</span
                    >
                    <span class="treasurercommunity-mod-alert">1</span>
                  </div>
                  <div class="treasurercommunity-mod-row">
                    <span class="treasurercommunity-mod-name"
                      >Fee Inquiries</span
                    >
                    <span class="treasurercommunity-mod-count">3</span>
                  </div>
                  <div class="treasurercommunity-mod-row">
                    <span class="treasurercommunity-mod-name"
                      >Unanswered Comments</span
                    >
                    <span class="treasurercommunity-mod-alert">4</span>
                  </div>
                </div>

                <!-- Online Staff -->
                <div class="treasurercommunity-widget">
                  <div class="treasurercommunity-widget-head">
                    <h5>Online Staff</h5>
                  </div>
                  <div class="treasurercommunity-contact-row">
                    <span class="treasurercommunity-online-dot"></span>
                    <span>Barangay Captain</span>
                  </div>
                  <div class="treasurercommunity-contact-row">
                    <span class="treasurercommunity-online-dot"></span>
                    <span>Secretary</span>
                  </div>
                  <div
                    class="treasurercommunity-contact-row"
                    style="opacity: 0.5"
                  >
                    <span
                      class="treasurercommunity-online-dot"
                      style="background: var(--treasurercommunity-border)"
                    ></span>
                    <span>SK Chairwoman</span>
                  </div>
                  <div
                    class="treasurercommunity-contact-row"
                    style="opacity: 0.5"
                  >
                    <span
                      class="treasurercommunity-online-dot"
                      style="background: var(--treasurercommunity-border)"
                    ></span>
                    <span>Kagawad</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      /* compose modal */
      function openCompose() {
        document.getElementById("composeModal").classList.add("open");
        document.body.style.overflow = "hidden";
        setTimeout(function () {
          document.getElementById("postTextarea").focus();
        }, 80);
      }

      function closeCompose() {
        document.getElementById("composeModal").classList.remove("open");
        document.body.style.overflow = "";
      }

      function closeComposeOnBg(e) {
        if (e.target === document.getElementById("composeModal"))
          closeCompose();
      }

      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeCompose();
      });

      /* comment toggle */
      function toggleComments(postId) {
        var section = document.getElementById("comments-" + postId);
        if (!section) return;
        var isOpen = section.classList.contains("open");
        section.classList.toggle("open", !isOpen);
        if (!isOpen) {
          var input = section.querySelector("input");
          if (input) input.focus();
        }
      }

      /* feed search */
      document
        .getElementById("feedSearch")
        .addEventListener("input", function () {
          var query = this.value.toLowerCase().trim();
          document
            .querySelectorAll(".treasurercommunity-post-item")
            .forEach(function (post) {
              var poster = post.dataset.poster || "";
              var body = post.dataset.body || "";
              post.style.display =
                !query || poster.includes(query) || body.includes(query)
                  ? ""
                  : "none";
            });
        });
    </script>
  </body>
</html>
