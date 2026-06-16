{{-- 우편번호/주소 검색 (Daum Postcode). 버튼에서 findAddress(cb) 호출, cb({postcode, address}) --}}
@once
@push('scripts')
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
    function findAddress(callback) {
        if (typeof daum === 'undefined' || !daum.Postcode) {
            alert('주소 검색 모듈을 불러오지 못했습니다. 잠시 후 다시 시도해 주세요.');
            return;
        }
        new daum.Postcode({
            oncomplete: function (data) {
                callback({
                    postcode: data.zonecode,
                    address: data.roadAddress || data.jibunAddress,
                });
            },
        }).open();
    }
</script>
@endpush
@endonce
