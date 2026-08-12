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

        /** 리스트/상세보기 탭 전환. 표준 id 규칙: {id}-tabList/-tabDetail/-btnList/-btnDetail */
        switchTab(id, which) {
            const onList = which === 'list';
            document.getElementById(id + '-tabList').classList.toggle('hidden', !onList);
            document.getElementById(id + '-tabDetail').classList.toggle('hidden', onList);
            [[id + '-btnList', onList], [id + '-btnDetail', !onList]].forEach(([bid, act]) => {
                const b = document.getElementById(bid);
                if (!b) return;
                b.classList.toggle('border-mango-500', act);
                b.classList.toggle('text-mango-600', act);
                b.classList.toggle('border-transparent', !act);
                b.classList.toggle('text-neutral-400', !act);
            });
        },
        /** 상세를 상세보기 탭(iframe ?panel=1)으로 연다 */
        openDetail(id, url, title) {
            const sep = url.includes('?') ? '&' : '?';
            document.getElementById(id + '-frame').src = url + sep + 'panel=1';
            const full = document.getElementById(id + '-full'); if (full) full.href = url;
            const te = document.getElementById(id + '-detailTitle'); if (te) te.textContent = title || '상세';
            const le = document.getElementById(id + '-detailLabel'); if (le) le.textContent = title ? (' · ' + title) : '';
            const bd = document.getElementById(id + '-btnDetail'); if (bd) { bd.disabled = false; bd.classList.remove('text-neutral-300'); }
            ww.switchTab(id, 'detail');
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
