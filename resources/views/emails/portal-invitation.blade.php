<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#f5f5f5;font-family:'Apple SD Gothic Neo',Arial,sans-serif;color:#262626;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:32px 0;">
        <tr><td align="center">
            <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <tr><td style="background:linear-gradient(135deg,#fbbf24,#f59e0b);padding:28px 32px;">
                    <span style="font-size:22px;font-weight:900;color:#fff;">🥭 LEEFRIENDS</span>
                    <p style="margin:6px 0 0;color:#fff8;font-size:13px;font-weight:600;">{{ $roleLabel }} 포털 초대</p>
                </td></tr>
                <tr><td style="padding:32px;">
                    <p style="font-size:16px;font-weight:700;margin:0 0 12px;">{{ $orgName }} 담당자님, 안녕하세요.</p>
                    <p style="font-size:14px;line-height:1.7;color:#525252;margin:0 0 24px;">
                        리프렌즈 본사가 <b>{{ $orgName }}</b>를 {{ $roleLabel }} 포털에 초대했습니다.<br>
                        아래 버튼을 눌러 <b>비밀번호를 설정</b>하시면 포털을 사용하실 수 있습니다.
                    </p>
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                        <tr><td align="center" style="border-radius:12px;background:#f59e0b;">
                            <a href="{{ $inviteUrl }}" style="display:inline-block;padding:14px 36px;color:#fff;font-weight:800;font-size:15px;text-decoration:none;">비밀번호 설정하기</a>
                        </td></tr>
                    </table>
                    <p style="font-size:12px;color:#a3a3a3;line-height:1.6;margin:0;">
                        버튼이 작동하지 않으면 아래 주소를 복사해 브라우저에 붙여넣어 주세요.<br>
                        <a href="{{ $inviteUrl }}" style="color:#f59e0b;word-break:break-all;">{{ $inviteUrl }}</a>
                    </p>
                </td></tr>
                <tr><td style="padding:18px 32px;background:#fafafa;border-top:1px solid #eee;">
                    <p style="font-size:11px;color:#a3a3a3;margin:0;">본 메일은 리프렌즈 발주포털에서 발송되었습니다. 초대를 요청하지 않으셨다면 이 메일을 무시해 주세요.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
