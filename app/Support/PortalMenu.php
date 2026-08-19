<?php

namespace App\Support;

/**
 * 포털 사이드바 2레벨 메뉴 정의.
 * 각 그룹 = [그룹명, 아이콘, 자식[]]. 자식 = [route, label, also[]].
 * 레이아웃(사이드바)과 환경설정(메뉴 표시/숨김) 화면이 공유한다.
 */
class PortalMenu
{
    public static function menus(): array
    {
        return [
            'hq' => [
                ['대시보드', '📊', [['portal.dashboard', '대시보드', []]]],
                ['채팅', '💬', [['portal.chat.index', '채팅', []]]],
                ['주문 · 판매', '📦', [
                    ['portal.hq.orders.index', '매장 발주 주문', ['portal.hq.orders.show']],
                    ['portal.hq.sales_orders.index', '판매주문', []],
                    ['portal.hq.purchase_orders.index', '공급처 구매발주', ['portal.hq.purchase_orders.create']],
                    ['portal.hq.supplier_orders.index', '공급사 발주 현황', []],
                ]],
                ['물류관리', '🚚', [
                    ['portal.hq.logistics.inbound', '입고관리', []],
                    ['portal.hq.logistics.inventory', '재고관리', []],
                    ['portal.hq.shipments.index', '출고관리', ['portal.hq.shipments.create', 'portal.hq.shipments.show']],
                ]],
                ['정산 · 현황', '📈', [
                    ['portal.hq.sales', '매출 현황', []],
                    ['portal.hq.statements.create', '거래명세서 작성', []],
                    ['portal.hq.statements.index', '거래명세서 이력', []],
                    ['portal.hq.tax_invoices.create', '세금계산서 발행', []],
                    ['portal.hq.tax_invoices.index', '세금계산서 발행이력', []],
                    ['portal.hq.invoices.index', '세금계산서(수취)', []],
                    ['portal.hq.hometax.index', '매출/매입 관리', []],
                    ['portal.hq.bank.index', '계좌 입금확인', []],
                    ['portal.hq.store_payments.index', '매장별 입금현황', ['portal.hq.store_payments.show']],
                    ['portal.hq.store_ledger.index', '매장 원장(정산)', ['portal.hq.store_ledger.show']],
                ]],
                ['기준정보', '🗂️', [
                    ['portal.hq.products.index', '품목 관리', []],
                    ['portal.hq.categories.index', '카테고리 관리', []],
                    ['portal.hq.suppliers.index', '공급처 관리', []],
                    ['portal.hq.stores.index', '매장 관리', []],
                    ['portal.hq.registrations.index', '회원 승인', []],
                    ['portal.hq.couriers.index', '택배사 관리', []],
                    ['portal.hq.fruit_storages.index', '과일 보관 관리', []],
                ]],
                ['홈페이지', '🏠', [
                    ['portal.hq.menus.index', '메뉴 관리', []],
                    ['portal.hq.blog.index', '블로그', []],
                    ['portal.hq.clips.index', '네이버 클립', []],
                    ['portal.hq.analytics.index', '방문 분석', []],
                ]],
                ['일정 관리', '📅', [['portal.schedules.index', '일정 관리', []]]],
                ['직원 관리', '👥', [['portal.staff.index', '직원 관리', []]]],
                ['공지사항', '📢', [['portal.hq.notices.index', '공지사항', []]]],
                ['창업 문의', '📨', [
                    ['portal.hq.inquiries.index', '창업 문의', ['portal.hq.inquiries.show']],
                ]],
                ['환경 설정', '⚙️', [['portal.hq.settings.index', '환경 설정', []]]],
            ],
            'store' => [
                ['대시보드', '📊', [['portal.dashboard', '대시보드', []]]],
                ['채팅', '💬', [['portal.chat.index', '본사 채팅', []]]],
                ['공지사항', '📢', [['portal.notices.index', '공지사항', []]]],
                ['발주', '🛒', [
                    ['portal.store.orders.create', '재료 발주하기', []],
                    ['portal.store.orders.index', '발주 내역', ['portal.store.orders.show', 'portal.store.orders.edit']],
                    ['portal.store.sample_orders.create', '샘플 주문하기', []],
                    ['portal.store.sample_orders.index', '샘플 주문 내역', []],
                ]],
                ['입고 · 재고', '📦', [
                    ['portal.store.inbound', '입고예정 · 배송', ['portal.store.shipments.show']],
                    ['portal.store.inventory.index', '재고 관리', ['portal.store.inventory.movements']],
                ]],
                ['현황', '📈', [
                    ['portal.store.purchases', '구매 현황', []],
                    ['portal.store.statements.index', '거래명세서(수취)', []],
                    ['portal.store.tax_invoices.index', '세금계산서', ['portal.store.tax_invoices.show']],
                ]],
                ['보관 가이드', '🧊', [['portal.store.fruit_storages.index', '과일 보관 가이드', []]]],
                ['일정 관리', '📅', [['portal.schedules.index', '일정 관리', []]]],
                ['직원 관리', '👥', [['portal.staff.index', '직원 관리', []]]],
            ],
            'supplier' => [
                ['대시보드', '📊', [['portal.dashboard', '대시보드', []]]],
                ['채팅', '💬', [['portal.chat.index', '본사 채팅', []]]],
                ['공지사항', '📢', [['portal.notices.index', '공지사항', []]]],
                ['물품', '🗂️', [
                    ['portal.supplier.products.index', '물품 관리', []],
                ]],
                ['주문 · 판매', '📦', [
                    ['portal.supplier.orders.index', '주문 관리', ['portal.supplier.orders.show']],
                    ['portal.supplier.sales_orders.index', '판매주문', []],
                    ['portal.supplier.purchase_orders.index', '본사 구매발주', []],
                ]],
                ['출고 · 배송', '🚚', [
                    ['portal.supplier.shipments.index', '출고 관리', ['portal.supplier.shipments.create', 'portal.supplier.shipments.show']],
                ]],
                ['정산 · 현황', '📈', [
                    ['portal.supplier.sales', '매출 현황', []],
                    ['portal.supplier.statements.create', '거래명세서 작성', []],
                    ['portal.supplier.statements.index', '거래명세서 이력', []],
                    ['portal.supplier.invoices.index', '세금계산서 발행이력', ['portal.supplier.invoices.create']],
                ]],
                ['일정 관리', '📅', [['portal.schedules.index', '일정 관리', []]]],
                ['직원 관리', '👥', [['portal.staff.index', '직원 관리', []]]],
            ],
        ];
    }

    public static function for(string $role): array
    {
        $menus = self::menus();

        return $menus[$role] ?? $menus['hq'];
    }

    /** 그룹을 펼쳐 개별 메뉴 목록으로 [{group, label, route}] */
    public static function flat(string $role): array
    {
        $rows = [];
        foreach (self::for($role) as [$group, $icon, $children]) {
            foreach ($children as [$route, $label]) {
                $rows[] = ['group' => $group, 'icon' => $icon, 'label' => $label, 'route' => $route];
            }
        }

        return $rows;
    }
}
