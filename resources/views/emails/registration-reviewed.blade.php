<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#f5f5f5;font-family:'Apple SD Gothic Neo',Arial,sans-serif;color:#262626;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:32px 0;">
        <tr><td align="center">
            <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <tr><td style="background:linear-gradient(135deg,#fbbf24,#f59e0b);padding:28px 32px;">
                    <span style="font-size:22px;font-weight:900;color:#fff;">🥭 LEEFRIENDS</span>
                    <p style="margin:6px 0 0;color:#fff8;font-size:13px;font-weight:600;">회원가입 {{ $approved ? '승인' : '반려' }} 안내</p>
                </td></tr>
                <tr><td style="padding:32px;">
                    <p style="font-size:16px;font-weight:700;margin:0 0 12px;">{{ $user->name }} 님, 안녕하세요.</p>
                    @if ($approved)
                        <p style="font-size:14px;line-height:1.7;color:#525252;margin:0 0 24px;">
                            신청하신 <b>{{ $user->signup_type_label }}</b> 회원가입이 <b style="color:#16a34a;">승인</b>되었습니다.<br>
                            아래 버튼을 눌러 로그인하시면 포털을 이용하실 수 있습니다.
                        </p>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 8px;">
                            <tr><td align="center" style="border-radius:12px;background:#f59e0b;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;padding:14px 36px;color:#fff;font-weight:800;font-size:15px;text-decoration:none;">포털 로그인</a>
                            </td></tr>
                        </table>
                    @else
                        <p style="font-size:14px;line-height:1.7;color:#525252;margin:0 0 16px;">
                            신청하신 <b>{{ $user->signup_type_label }}</b> 회원가입이 <b style="color:#dc2626;">반려</b>되었습니다.
                        </p>
                        @if ($reason)
                            <p style="font-size:13px;line-height:1.6;color:#525252;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px 16px;margin:0 0 16px;">
                                <b>사유</b><br>{{ $reason }}
                            </p>
                        @endif
                        <p style="font-size:13px;line-height:1.6;color:#737373;margin:0;">
                            문의사항이 있으시면 본사로 연락해 주세요.
                        </p>
                    @endif
                </td></tr>
                <tr><td style="padding:18px 32px;background:#fafafa;border-top:1px solid #eee;">
                    <p style="font-size:11px;color:#a3a3a3;margin:0;">본 메일은 리프렌즈 발주포털에서 발송되었습니다.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
