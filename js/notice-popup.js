/**
 * Programmers Lab — Site Notice Popup
 * Har page pe include karo — admin se active notice auto load hoga
 */
(function () {
    // Har page load pe notice show hoga
    // In-memory track — sirf us page load pe dobara na dikhe (e.g. AJAX reload)
    const _seenThisLoad = new Set();

    function alreadySeen(id) {
        return _seenThisLoad.has(id);
    }

    function markSeen(id) {
        _seenThisLoad.add(id);
    }

    function closePopup(id) {
        const overlay = document.getElementById('pl-notice-overlay');
        const popup   = document.getElementById('pl-notice-popup');
        if (popup) {
            popup.style.opacity   = '0';
            popup.style.transform = 'translateY(20px)';
        }
        if (overlay) {
            overlay.style.opacity    = '0';
            overlay.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                if (overlay && overlay.parentNode) overlay.remove();
            }, 300);
        }
        // Remove pointer events immediately so page is usable right away
        if (overlay) overlay.style.pointerEvents = 'none';
        document.body.style.overflow = '';
        markSeen(id);
    }

    function showNotice(n) {
        if (alreadySeen(n.id)) return;

        // Inject CSS once
        if (!document.getElementById('pl-notice-css')) {
            const style = document.createElement('style');
            style.id = 'pl-notice-css';
            style.textContent = `
                #pl-notice-overlay {
                    position: fixed; inset: 0;
                    background: rgba(0,0,0,0.6);
                    z-index: 99999;
                    display: flex; align-items: center; justify-content: center;
                    padding: 16px;
                    animation: plFadeIn 0.25s ease;
                }
                @keyframes plFadeIn { from { opacity:0; } to { opacity:1; } }
                #pl-notice-popup {
                    background: #fff;
                    border-radius: 20px;
                    padding: 0;
                    max-width: 580px;
                    width: 92%;
                    position: relative;
                    box-shadow: 0 24px 64px rgba(0,0,0,0.22);
                    text-align: center;
                    transition: opacity 0.3s, transform 0.3s;
                    overflow: hidden;
                }
                #pl-notice-popup .pl-np-body {
                    padding: 28px 28px 24px;
                }
                #pl-notice-popup .pl-np-img {
                    width: 100%;
                    display: block;
                    max-height: 320px;
                    object-fit: cover;
                    border-radius: 0;
                    margin: 0;
                }
                #pl-notice-popup .pl-np-close {
                    position: absolute; top: 12px; right: 12px;
                    width: 34px; height: 34px;
                    background: rgba(0,0,0,0.45); border: none; border-radius: 50%;
                    cursor: pointer; font-size: 15px; color: #fff;
                    display: flex; align-items: center; justify-content: center;
                    transition: background 0.2s;
                    z-index: 2;
                }
                #pl-notice-popup .pl-np-close:hover { background: rgba(0,0,0,0.65); }
                #pl-notice-popup .pl-np-badge {
                    display: inline-block;
                    padding: 5px 14px; border-radius: 20px;
                    font-size: 12px; font-weight: 700;
                    margin-bottom: 12px;
                }
                #pl-notice-popup .pl-np-icon {
                    width: 56px; height: 56px; border-radius: 16px;
                    background: rgba(240,123,20,0.1);
                    display: flex; align-items: center; justify-content: center;
                    font-size: 24px; margin: 0 auto 14px;
                }
                #pl-notice-popup h3 {
                    color: #0d1b2a; font-size: 20px; font-weight: 800;
                    margin: 0 0 10px; line-height: 1.35;
                    font-family: 'Raleway', 'Inter', sans-serif;
                }
                #pl-notice-popup p {
                    color: #6b7280; font-size: 14.5px; line-height: 1.7;
                    margin: 0 0 20px;
                    font-family: 'Raleway', 'Inter', sans-serif;
                }
                #pl-notice-popup .pl-np-btn {
                    display: inline-flex; align-items: center; gap: 8px;
                    padding: 12px 28px;
                    border-radius: 50px; font-size: 14px; font-weight: 700;
                    text-decoration: none; color: #fff;
                    transition: transform 0.2s, box-shadow 0.2s;
                    font-family: 'Raleway', 'Inter', sans-serif;
                }
                #pl-notice-popup .pl-np-btn:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 8px 20px rgba(240,123,20,0.35);
                    color: #fff; text-decoration: none;
                }
                #pl-notice-popup .pl-np-dismiss {
                    display: block; margin-top: 14px;
                    font-size: 13px; color: #9ca3af; cursor: pointer;
                    background: none; border: none;
                    font-family: 'Raleway', 'Inter', sans-serif;
                    transition: color 0.2s;
                }
                #pl-notice-popup .pl-np-dismiss:hover { color: #374151; }
                @media (max-width: 600px) {
                    #pl-notice-overlay { padding: 10px; }
                    #pl-notice-popup { width: 98%; border-radius: 16px; }
                    #pl-notice-popup .pl-np-img { max-height: 200px; }
                    #pl-notice-popup .pl-np-body { padding: 20px 18px 18px; }
                    #pl-notice-popup h3 { font-size: 17px; }
                    #pl-notice-popup p { font-size: 13.5px; }
                }
            `;
            document.head.appendChild(style);
        }

        const color    = n.badge_color || '#f07b14';
        const badgeHtml = n.badge
            ? `<span class="pl-np-badge" style="background:${color}22;color:${color};">${escHtml(n.badge)}</span><br>`
            : '';
        const btnHtml = n.btn_text && n.btn_url
            ? `<a href="${escHtml(n.btn_url)}" class="pl-np-btn" style="background:${color};">${escHtml(n.btn_text)} →</a>`
            : '';
        const imgHtml = n.image
            ? `<img src="uploads/${escHtml(n.image)}" alt="" class="pl-np-img">`
            : '';

        const overlay = document.createElement('div');
        overlay.id = 'pl-notice-overlay';
        overlay.innerHTML = `
            <div id="pl-notice-popup">
                <button class="pl-np-close" onclick="window.__plCloseNotice(${n.id})">✕</button>
                ${imgHtml}
                <div class="pl-np-body">
                    <div class="pl-np-icon">📢</div>
                    ${badgeHtml}
                    <h3>${escHtml(n.title)}</h3>
                    <p>${escHtml(n.message)}</p>
                    ${btnHtml}
                    <button class="pl-np-dismiss" onclick="window.__plCloseNotice(${n.id})">Dismiss</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // Close on backdrop
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) window.__plCloseNotice(n.id);
        });

        // Close on Escape
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') { window.__plCloseNotice(n.id); document.removeEventListener('keydown', esc); }
        });
    }

    function escHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Global close function
    window.__plCloseNotice = closePopup;

    // Fetch active notice after page load
    window.addEventListener('load', function () {
        // Detect base path — works from any subdirectory
        const base = (function() {
            const scripts = document.querySelectorAll('script[src]');
            for (let s of scripts) {
                const m = s.src.match(/^(https?:\/\/[^/]+)(\/.*?)js\/notice-popup\.js/);
                if (m) return m[1] + m[2];
            }
            // fallback: use origin
            return window.location.origin + '/';
        })();

        setTimeout(function () {
            fetch(base + 'api/notice.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.notice) {
                        showNotice(data.notice);
                    }
                })
                .catch(() => {});
        }, 800);
    });
})();
