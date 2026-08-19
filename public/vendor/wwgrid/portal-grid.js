/* wwGrid 포털 공용 헬퍼 — 리스트 그리드 + 리스트/상세보기 탭 전환.
   화면별 스크립트에서 ww.* 를 사용한다. (wwGrid.js 다음에 로드) */
(function () {
    function el(tag, cls, text) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }
    const ww = {
        el,
        /** 색상 배지 노드 */
        badge(text, cls) {
            return el('span', 'inline-block text-xs font-bold px-2.5 py-1 rounded-full ' + (cls || 'bg-neutral-100 text-neutral-600'), text);
        },
        /** 금액(원) */
        won(v) { return (Number(v) || 0).toLocaleString() + '원'; },
        /** 천단위 숫자 */
        num(v) { return (Number(v) || 0).toLocaleString(); },
        /** 회색 대시(빈 값) */
        dash() { return el('span', 'text-neutral-300', '—'); },

        /** 우하단 토스트 알림 (#toast-container 사용, 없으면 무시) */
        toast(title, body) {
            const c = document.getElementById('toast-container');
            if (!c) { return; }
            const box = document.createElement('div');
            box.className = 'pointer-events-auto rounded-xl bg-white shadow-lg border border-neutral-200 px-4 py-3 flex items-start gap-3 translate-x-6 opacity-0 transition-all duration-300';
            const icon = el('span', 'text-xl shrink-0', '✅');
            const txt = document.createElement('div'); txt.className = 'min-w-0 flex-1';
            txt.appendChild(el('p', 'text-sm font-bold text-neutral-900', title || '완료'));
            if (body) txt.appendChild(el('p', 'text-xs text-neutral-500 mt-0.5 break-words', body));
            box.appendChild(icon); box.appendChild(txt);
            c.appendChild(box);
            requestAnimationFrame(() => box.classList.remove('translate-x-6', 'opacity-0'));
            setTimeout(() => { box.classList.add('translate-x-6', 'opacity-0'); setTimeout(() => box.remove(), 300); }, 4000);
        },

        /** 리스트/상세보기 탭 전환. 표준 id 규칙: {id}-tabList/-tabDetail/-btnList/-btnDetail */
        switchTab(id, which) {
            const onList = which === 'list';
            document.getElementById(id + '-tabList').classList.toggle('hidden', !onList);
            document.getElementById(id + '-tabDetail').classList.toggle('hidden', onList);
            [[id + '-btnList', onList], [id + '-btnDetail', !onList]].forEach(([bid, act]) => {
                const b = document.getElementById(bid);
                if (!b) return;
                // ce-admin 세그먼트 컨트롤: 활성 = 흰색 알약
                b.classList.toggle('bg-white', act);
                b.classList.toggle('text-neutral-900', act);
                b.classList.toggle('shadow-sm', act);
                b.classList.toggle('text-neutral-400', !act);
            });
        },
        /** 상세를 상세보기 탭에 직접 임베드(AJAX). iframe 을 쓰지 않는다.
         *  서버의 ?panel=1 페이지에서 #panel-root 만 가져와 삽입하고, 인라인 스크립트 재실행 +
         *  Alpine 초기화. 상세 내부의 페이지이동 링크는 리스트 탭으로 전환. */
        openDetail(id, url, title) {
            const box = document.getElementById(id + '-detail');
            const full = document.getElementById(id + '-full'); if (full) full.href = url;
            const te = document.getElementById(id + '-detailTitle'); if (te) te.textContent = title || '상세';
            const le = document.getElementById(id + '-detailLabel'); if (le) le.textContent = title ? (' · ' + title) : '';
            const bd = document.getElementById(id + '-btnDetail'); if (bd) { bd.disabled = false; bd.classList.remove('text-neutral-300'); }
            ww.switchTab(id, 'detail');
            if (!box) return;
            box.dataset.detailUrl = url;
            // 상세 내부 폼 제출 → 전체 페이지 이동 대신 AJAX 전송(현재 탭 유지) + 토스트 + 상세 재로딩.
            // (box 는 innerHTML 만 교체되고 요소 자체는 유지되므로 리스너는 1회만 등록)
            if (!box.__wwFormBound) {
                box.__wwFormBound = true;
                box.addEventListener('submit', function (e) {
                    const form = e.target;
                    if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-ajax')) return;
                    if ((form.getAttribute('method') || 'get').toUpperCase() === 'GET' || form.target === '_blank') return;
                    // 인라인 onsubmit 이 이미 막았으면(confirm 취소·단가 차단 등) AJAX 전송도 하지 않음
                    if (e.defaultPrevented) return;
                    e.preventDefault();
                    const btn = form.querySelector('[type="submit"]');
                    if (btn) btn.disabled = true;
                    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' }, redirect: 'follow' })
                        .then((r) => r.text().then((t) => ({ ok: r.ok, t })))
                        .then(({ ok, t }) => {
                            let err = null, msg = form.getAttribute('data-ajax-toast') || '처리되었습니다.';
                            try {
                                const d = new DOMParser().parseFromString(t, 'text/html');
                                const e2 = d.querySelector('.bg-rose-50.text-rose-700');
                                const s2 = d.querySelector('.bg-emerald-50.text-emerald-700');
                                if (e2 && e2.textContent.trim()) err = e2.textContent.trim().slice(0, 160);
                                else if (s2 && s2.textContent.trim()) msg = s2.textContent.trim().slice(0, 160);
                            } catch (_) {}
                            ww.toast(err ? '⚠ 실패' : '✅ 완료', err || msg);
                            ww.openDetail(id, box.dataset.detailUrl, (document.getElementById(id + '-detailTitle') || {}).textContent || '');
                        })
                        .catch(() => { if (btn) btn.disabled = false; ww.toast('⚠ 오류', '전송 중 오류가 발생했습니다.'); });
                });
            }
            box.innerHTML = '<div class="p-10 text-center text-neutral-400 text-sm">불러오는 중…</div>';
            const sep = url.includes('?') ? '&' : '?';
            fetch(url + sep + 'panel=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => r.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const root = doc.getElementById('panel-root');
                    box.innerHTML = root ? root.innerHTML : '<div class="p-10 text-center text-rose-500 text-sm">상세를 불러오지 못했습니다.</div>';
                    // #panel-root 안의 뷰 인라인 스크립트만 재실행(공유 레이아웃/라이브러리 스크립트 제외)
                    if (root) {
                        root.querySelectorAll('script').forEach((old) => {
                            if (old.getAttribute('src')) return;
                            const s = document.createElement('script');
                            s.textContent = old.textContent;
                            box.appendChild(s);
                        });
                    }
                    // 상세 내부 링크 처리
                    box.querySelectorAll('a[href]').forEach((a) => {
                        // «← ...» 뒤로가기 링크는 탭 헤더의 «← 리스트» 와 중복 → 숨김
                        if (a.textContent.trim().charAt(0) === '←') { a.style.display = 'none'; return; }
                        const href = a.getAttribute('href');
                        if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download') || href.toLowerCase().startsWith('javascript:')) return;
                        // 그 외 페이지이동 링크 → 리스트 탭으로 전환(전체 페이지 이동 방지)
                        a.addEventListener('click', (e) => { e.preventDefault(); ww.switchTab(id, 'list'); });
                    });
                    // Alpine 초기화(상세 내 x-data)
                    if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(box);
                })
                .catch(() => { box.innerHTML = '<div class="p-10 text-center text-rose-500 text-sm">불러오기 오류가 발생했습니다.</div>'; });
        },
        /** 그리드 행 클릭 → 상세보기 탭. urlField/titleField 는 row 의 키 이름 */
        bindRowDetail(id, grid, urlField, titleField) {
            document.getElementById(id).addEventListener('click', function (e) {
                if (e.target.closest('a, button, input, select, form')) return;
                const cell = e.target.closest('[data-row-index]');
                if (!cell) return;
                const row = grid.getData()[parseInt(cell.dataset.rowIndex, 10)];
                if (!row) return;
                window.getSelection()?.removeAllRanges();
                ww.openDetail(id, row[urlField], titleField ? row[titleField] : '');
            });
        },
        /** 목록 그리드 기본 옵션 + 사용자 옵션 병합.
         *  기본 height:'fit' → 페이지 스크롤 없이 그리드 내부만 스크롤(한 화면에 맞춤).
         *  (사이드바는 레이아웃에서 sticky h-screen 이라 페이지 높이를 늘리지 않음) */
        grid(id, columns, data, options) {
            const g = new wwGrid(Object.assign({
                el: document.getElementById(id),
                editable: false, rowCheckbox: true, rowNumber: true, toolbar: true,
                height: 'fit',
                footer: { total: true, selected: true, modified: false },
                columns: columns, data: data || [],
            }, options || {}));
            // 초기 렌더/폰트 로딩 이후 fit 높이 재계산(측정 타이밍 보정)
            requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
            setTimeout(() => window.dispatchEvent(new Event('resize')), 250);
            return g;
        },
    };
    window.ww = ww;
})();
