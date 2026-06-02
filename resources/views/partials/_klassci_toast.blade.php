<script>
    if (typeof window.klassciToast !== 'function') {
        window.klassciToast = function (type, message, duration) {
            duration = duration || 4500;
            let host = document.getElementById('klassci-toast-host');
            if (!host) {
                host = document.createElement('div');
                host.id = 'klassci-toast-host';
                host.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px;';
                document.body.appendChild(host);
            }
            const colors = {
                success: { bg: '#10b981', icon: 'fa-circle-check' },
                error:   { bg: '#dc2626', icon: 'fa-circle-xmark' },
                warning: { bg: '#f59e0b', icon: 'fa-triangle-exclamation' },
                info:    { bg: '#0453cb', icon: 'fa-circle-info' },
            };
            const cfg = colors[type] || colors.info;
            const toast = document.createElement('div');
            toast.style.cssText = `display:flex;align-items:flex-start;gap:.6rem;padding:.85rem 1rem;border-radius:11px;background:#fff;border-left:4px solid ${cfg.bg};box-shadow:0 10px 30px rgba(15,23,42,.18);font-size:.86rem;color:#1e293b;animation:klassciToastIn .25s ease;`;
            toast.innerHTML = `<i class="fas ${cfg.icon}" style="color:${cfg.bg};margin-top:2px;"></i><div style="flex:1;min-width:0;line-height:1.4;">${message}</div><button type="button" style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;margin-left:.5rem;font-size:.9rem;" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
            host.appendChild(toast);
            if (!document.getElementById('klassci-toast-keyframes')) {
                const sty = document.createElement('style');
                sty.id = 'klassci-toast-keyframes';
                sty.textContent = '@keyframes klassciToastIn{from{transform:translateX(20px);opacity:0}to{transform:translateX(0);opacity:1}}';
                document.head.appendChild(sty);
            }
            if (duration > 0) {
                setTimeout(() => { toast.style.animation = 'klassciToastIn .2s reverse'; setTimeout(() => toast.remove(), 200); }, duration);
            }
        };
        window.addEventListener('toast', (ev) => {
            if (ev?.detail?.message) window.klassciToast(ev.detail.type || 'info', ev.detail.message);
        });
    }
</script>
