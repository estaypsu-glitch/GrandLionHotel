<style>
    .auth-premium-shell {
        border-radius: 24px;
        border: 1px solid #e4d9c9;
        box-shadow: 0 20px 50px rgba(18, 24, 39, 0.12);
    }
    .auth-premium-visual {
        position: relative;
        min-height: 580px;
    }
    .auth-premium-visual img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .auth-premium-overlay {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(175deg, rgba(12, 20, 34, 0.2) 0%, rgba(12, 20, 34, 0.78) 66%, rgba(12, 20, 34, 0.9) 100%),
            radial-gradient(circle at 14% 18%, rgba(184, 146, 84, 0.48), transparent 35%);
    }
    .auth-premium-copy {
        position: absolute;
        left: 1.8rem;
        right: 1.8rem;
        bottom: 1.8rem;
        z-index: 1;
        color: #f8fafc;
    }
    .auth-premium-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.14);
        padding: 0.34rem 0.75rem;
        color: #fff;
        font-size: 0.73rem;
        font-weight: 700;
    }
    .auth-premium-form-pane {
        padding: 1.5rem;
    }
    .auth-brand-signature {
        display: flex;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .auth-brand-mark {
        width: 80px;
        height: 80px;
        object-fit: contain;
        flex-shrink: 0;
        filter: drop-shadow(0 2px 8px rgba(17, 24, 39, 0.15)) contrast(1.05);
    }
    .auth-premium-switch {
        display: inline-flex;
        gap: 0.28rem;
        border: 1px solid #eadfcf;
        border-radius: 999px;
        padding: 0.22rem;
        background: #f9f5ee;
    }
    .auth-premium-switch-link {
        border-radius: 999px;
        padding: 0.46rem 0.95rem;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        color: #5c6471;
    }
    .auth-premium-switch-link.active {
        background: #fff;
        color: #162033;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .auth-premium-card {
        border: 1px solid #e8ddcd;
        border-radius: 20px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(252, 248, 241, 0.95) 100%);
        padding: 1.3rem;
    }
    .auth-oauth-btn {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #d8cbb7;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        color: #1f2937;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        text-decoration: none;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .auth-oauth-btn:hover {
        border-color: #b89254;
        box-shadow: 0 10px 22px rgba(184, 146, 84, 0.18);
        color: #111827;
        transform: translateY(-1px);
    }
    .auth-oauth-icon {
        width: 1.45rem;
        height: 1.45rem;
        border-radius: 999px;
        border: 1px solid #e6dccd;
        background: #fff;
        mask-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23DB4437" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="%234285F4" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="%2334A853" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.83l2.66-2.74z"/><path fill="%23FBBC05" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.72c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>'); 
        mask-size: contain;
        mask-position: center;
        mask-repeat: no-repeat;
        background: #4285f4;
        color: transparent;
        font-size: 0;
        width: 1.8rem;
        height: 1.8rem;
    }
    .auth-oauth-divider {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        color: #8a93a3;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }
    .auth-oauth-divider::before,
    .auth-oauth-divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #e5dac8;
    }
    .auth-premium-label {
        margin-bottom: 0.4rem;
        color: #677286;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .auth-premium-input {
        height: 48px;
        border-radius: 12px;
        border: 1px solid #ddcfba;
        background-color: #fff;
    }
    .auth-premium-input:focus {
        border-color: rgba(184, 146, 84, 0.75);
        box-shadow: 0 0 0 0.2rem rgba(184, 146, 84, 0.22);
    }
    .auth-password-wrap {
        position: relative;
    }
    .auth-password-wrap input[type="password"]::-ms-reveal,
    .auth-password-wrap input[type="password"]::-ms-clear {
        display: none;
    }
    .auth-password-wrap .auth-premium-input {
        padding-right: 2.9rem;
    }
    .auth-password-toggle {
        position: absolute;
        right: 0.45rem;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #6a7280;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.36rem 0.5rem;
        line-height: 1;
    }
    .auth-password-toggle:hover {
        background: #f4ede2;
        color: #3c4453;
    }
    .auth-password-toggle:focus {
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(184, 146, 84, 0.22);
    }
    .auth-security-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.15rem;
    }
    .auth-security-chip {
        border-radius: 999px;
        border: 1px solid #e5d9c8;
        background: #fff;
        color: #576072;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        padding: 0.26rem 0.62rem;
    }
    .auth-field-hint {
        margin-top: 0.45rem;
        color: #6f7788;
        font-size: 0.78rem;
        line-height: 1.35;
    }
    .auth-premium-meta {
        border: 1px dashed #d9c8b0;
        border-radius: 14px;
        background: #f9f3ea;
        color: #6a7280;
        font-size: 0.86rem;
        padding: 0.7rem 0.9rem;
    }
    .auth-premium-action {
        width: 100%;
        border-radius: 12px;
        padding-top: 0.67rem;
        padding-bottom: 0.67rem;
        font-weight: 800;
    }
    .auth-premium-link {
        font-weight: 700;
        color: #1f2937;
        text-decoration: none;
        border-bottom: 1px solid rgba(31, 41, 55, 0.26);
    }
    .auth-premium-link:hover {
        color: #111827;
        border-color: #111827;
    }
    @media (max-width: 1199.98px) {
        .auth-premium-visual {
            min-height: 520px;
        }
    }
    @media (max-width: 991.98px) {
        .auth-premium-form-pane {
            padding: 1.15rem;
        }
        .auth-brand-signature {
            margin-bottom: 0.8rem;
        }
        .auth-brand-mark {
            width: 64px;
            height: 64px;
        }
    }
</style>
