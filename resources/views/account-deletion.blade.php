@extends('layouts.app')

@section('title', '계정 및 데이터 삭제 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'ACCOUNT DELETION',
    'title' => '계정 및 데이터 삭제 요청',
    'subtitle' => '리프렌즈 계정과 개인정보 삭제를 안내합니다',
])

<section class="py-16 md:py-24">
    <div class="max-w-3xl mx-auto px-5 lg:px-8">
        <div class="space-y-10 text-neutral-700 leading-relaxed">

            <p class="text-sm text-neutral-500">
                리프렌즈(LEEFRIENDS) 앱은 본사·매장·공급처를 위한 B2B 발주 관리 서비스로, 계정은 본사(주식회사 오다네트웍스)의
                초대를 통해 생성·관리됩니다. 계정 및 관련 개인정보의 삭제를 원하시는 경우 아래 절차에 따라 요청하실 수 있습니다.
            </p>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">삭제 요청 방법</h2>
                <p>아래 이메일로 다음 정보를 포함하여 요청해 주세요. 본인 확인 후 처리해 드립니다.</p>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li>상호(매장·공급처명) 및 대표자명</li>
                    <li>가입 시 사용한 이메일 또는 로그인 아이디</li>
                    <li>요청 내용 : “계정 및 개인정보 삭제 요청”</li>
                </ul>
                <div class="mt-5 rounded-2xl bg-neutral-50 border border-neutral-200 p-5 text-sm space-y-1.5">
                    <p><b>이메일</b> : <a href="mailto:jack@withworks.co.kr" class="text-mango-600 font-semibold hover:underline">jack@withworks.co.kr</a></p>
                    <p><b>운영사</b> : 주식회사 오다네트웍스 (대표 이윤석)</p>
                    <p class="text-neutral-500">접수 후 영업일 기준 신속히 처리하며, 처리 완료 시 회신드립니다.</p>
                </div>
                <p class="text-sm text-neutral-500 mt-3">
                    ※ 소속 본사 관리자에게 직접 계정 삭제·해지를 요청하셔도 됩니다.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">삭제되는 데이터</h2>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li>계정 정보 : 이름(상호), 이메일, 전화번호, 로그인 정보</li>
                    <li>푸시 알림용 기기 토큰</li>
                    <li>앱 내 채팅 메시지 및 첨부 이미지</li>
                </ul>
            </div>

            <div>
                <h2 class="text-xl font-black text-neutral-900 mb-3">보관되는 데이터 (법령에 따른 예외)</h2>
                <p>
                    관계 법령에 따라 아래 거래 관련 기록은 삭제 요청과 무관하게 명시된 기간 동안 보관된 후 파기됩니다.
                </p>
                <ul class="list-disc pl-5 mt-3 space-y-1.5">
                    <li>계약·청약철회·대금결제 및 재화 공급에 관한 기록 : 5년 (전자상거래법)</li>
                    <li>세금계산서·거래명세서 등 거래 증빙 : 5년 (국세기본법·부가가치세법)</li>
                    <li>소비자 불만 또는 분쟁 처리 기록 : 3년 (전자상거래법)</li>
                </ul>
                <p class="text-sm text-neutral-500 mt-3">
                    자세한 내용은 <a href="{{ route('privacy') }}" class="text-mango-600 font-semibold hover:underline">개인정보처리방침</a>을 참고해 주세요.
                </p>
            </div>

        </div>
    </div>
</section>

@endsection
