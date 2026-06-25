@extends('layouts.app')

@section('title', '개인정보처리방침 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'PRIVACY POLICY',
    'title' => '개인정보처리방침',
    'subtitle' => '리프렌즈는 이용자의 개인정보를 소중히 보호합니다',
])

<section class="py-16 md:py-24">
    <div class="max-w-3xl mx-auto px-5 lg:px-8">
        <div class="prose-leefriends space-y-10 text-neutral-700 leading-relaxed">

            <p class="text-sm text-neutral-500">
                주식회사 오다네트웍스(이하 ‘회사’)는 리프렌즈(LEEFRIENDS) 모바일 앱 및 웹 서비스(이하 ‘서비스’)를 운영하며,
                「개인정보 보호법」 등 관련 법령을 준수하여 이용자의 개인정보를 보호합니다. 본 방침은 회사가 어떤 정보를
                어떻게 수집·이용·보관·파기하는지 안내합니다.
            </p>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">1. 수집하는 개인정보 항목</h2>
                <p>회사는 서비스 제공을 위해 다음의 개인정보를 수집합니다.</p>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li><b>계정 정보</b> : 이름(상호), 이메일, 전화번호, 비밀번호, 소속(본사·매장·공급처) 구분</li>
                    <li><b>사업자 정보</b> : 사업자등록번호, 대표자명, 사업장 주소, 업태·종목 (전자세금계산서 발행 대상)</li>
                    <li><b>서비스 이용 기록</b> : 발주·주문 내역, 입출고·재고 기록, 매입·매출 내역</li>
                    <li><b>채팅 내용</b> : 본사–매장–공급처 간 메시지 및 첨부 이미지</li>
                    <li><b>기기 정보</b> : 푸시 알림 발송을 위한 기기 토큰(FCM), OS·앱 버전</li>
                    <li><b>카메라·사진</b> : 채팅 첨부용 사진 촬영·선택 시에 한해 접근 (이용자가 직접 첨부한 이미지만 전송)</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">2. 개인정보의 수집·이용 목적</h2>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li>회원 식별 및 로그인·인증, 서비스 제공·운영</li>
                    <li>발주·출고·입고·재고 등 거래 처리 및 이력 관리</li>
                    <li>전자세금계산서·거래명세서의 작성·발행·전송</li>
                    <li>주문 상태 변경, 공지 등 푸시 알림 발송</li>
                    <li>본사–매장–공급처 간 커뮤니케이션(채팅) 지원</li>
                    <li>고객 문의 응대 및 분쟁 처리</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">3. 개인정보의 보유 및 이용 기간</h2>
                <p>
                    회사는 원칙적으로 개인정보 수집·이용 목적이 달성되면 지체 없이 파기합니다. 다만 관계 법령에 따라
                    아래 정보는 명시된 기간 동안 보관합니다.
                </p>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li>계약 또는 청약철회 등에 관한 기록 : 5년 (전자상거래법)</li>
                    <li>대금결제 및 재화 등의 공급에 관한 기록 : 5년 (전자상거래법)</li>
                    <li>세금계산서·장부 등 거래 증빙 : 5년 (국세기본법·부가가치세법)</li>
                    <li>소비자 불만 또는 분쟁 처리에 관한 기록 : 3년 (전자상거래법)</li>
                    <li>계정 정보 : 회원 탈퇴 시까지 (탈퇴 후 위 법정 보존 기록 제외 즉시 파기)</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">4. 개인정보의 제3자 제공</h2>
                <p>
                    회사는 이용자의 개인정보를 본 방침에 명시한 범위를 넘어 외부에 제공하지 않습니다. 다만 다음의 경우는
                    예외로 합니다.
                </p>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li>이용자가 사전에 동의한 경우</li>
                    <li>법령에 의거하거나 수사기관의 적법한 요청이 있는 경우</li>
                    <li><b>전자세금계산서 발행</b> : 「부가가치세법」에 따라 거래 당사자의 사업자 정보 및 거래 내역이 국세청에 전송됩니다.</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">5. 개인정보 처리의 위탁</h2>
                <p>회사는 원활한 서비스 제공을 위해 다음과 같이 개인정보 처리 업무를 위탁하고 있습니다.</p>
                <div class="overflow-x-auto mt-3">
                    <table class="w-full text-sm border-t border-neutral-200">
                        <thead>
                            <tr class="border-b border-neutral-200 text-left text-neutral-500">
                                <th class="py-2.5 pr-4 font-semibold">수탁자</th>
                                <th class="py-2.5 font-semibold">위탁 업무</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr><td class="py-2.5 pr-4">Google LLC (Firebase)</td><td class="py-2.5">푸시 알림(FCM) 발송</td></tr>
                            <tr><td class="py-2.5 pr-4">(주)링크허브 (팝빌)</td><td class="py-2.5">전자세금계산서·계산서 발행 및 국세청 전송</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">6. 이용자의 권리와 행사 방법</h2>
                <p>
                    이용자는 언제든지 자신의 개인정보에 대한 열람·정정·삭제·처리정지를 요청할 수 있으며, 회원 탈퇴를 통해
                    개인정보 수집·이용 동의를 철회할 수 있습니다. 권리 행사는 아래 개인정보 보호책임자에게 서면·이메일로
                    요청하시면 지체 없이 조치합니다.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">7. 개인정보의 파기 절차 및 방법</h2>
                <p>
                    수집 목적이 달성된 개인정보는 재생이 불가능한 방법으로 파기합니다. 전자적 파일은 복구할 수 없도록
                    영구 삭제하며, 출력물은 분쇄하거나 소각합니다.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">8. 개인정보의 안전성 확보 조치</h2>
                <p>
                    회사는 개인정보 보호를 위해 비밀번호 암호화 저장, 접근 권한 관리, 전송 구간 암호화(HTTPS) 등
                    관리적·기술적 보호 조치를 시행하고 있습니다.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">9. 개인정보 보호책임자</h2>
                <div class="mt-3 rounded-2xl bg-neutral-50 border border-neutral-200 p-5 text-sm space-y-1.5">
                    <p><b>개인정보 보호책임자</b> : 이윤석 (대표)</p>
                    <p><b>상호</b> : 주식회사 오다네트웍스</p>
                    <p><b>사업자등록번호</b> : 827-81-03115</p>
                    <p><b>주소</b> : 경기도 의정부시 천보로 14, 1113호(민락동)</p>
                    <p><b>이메일</b> : privacy@leefriends.co.kr</p>
                </div>
                <p class="text-sm text-neutral-500 mt-3">
                    개인정보 침해에 관한 상담이 필요하신 경우 개인정보분쟁조정위원회(1833-6972),
                    개인정보침해신고센터(118), 대검찰청(1301), 경찰청(182)에 문의하실 수 있습니다.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">10. 고지의 의무</h2>
                <p>
                    본 개인정보처리방침의 내용 추가·삭제·수정이 있을 경우 시행 7일 전부터 서비스 내 공지를 통해
                    안내합니다.
                </p>
                <p class="mt-4 text-sm text-neutral-500">
                    · 공고일자 : 2026년 6월 25일<br>
                    · 시행일자 : 2026년 6월 25일
                </p>
            </div>

        </div>
    </div>
</section>

@endsection
