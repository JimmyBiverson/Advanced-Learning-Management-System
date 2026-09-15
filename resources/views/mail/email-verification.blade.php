<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <meta name="color-scheme" content="light" />
   <meta name="supported-color-schemes" content="light" />
   <title>Confirm your email address</title>
   <style>
      @media only screen and (max-width: 600px) {
         .container { width: 100% !important; padding: 12px !important; }
         .button { display: block !important; width: 100% !important; text-align: center !important; }
      }
   </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
   <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f3f4f6" style="background-color: #f3f4f6;">
      <tr>
         <td align="center" style="padding: 32px 16px;">
            <table class="container" role="presentation" width="600" cellpadding="0" cellspacing="0" style="width: 600px; max-width: 600px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px;">
               <tr>
                  <td style="padding: 32px 32px 8px 32px;">
                     <p style="margin: 0; font-size: 20px; font-weight: 700; color: #111827;">{{ config('app.name') }}</p>
                  </td>
               </tr>
               <tr>
                  <td style="padding: 16px 32px 32px 32px;">
                     <p style="margin: 0 0 16px 0; font-size: 16px; line-height: 1.5; color: #111827;">Hi {{ $user->name }},</p>
                     <p style="margin: 0 0 16px 0; font-size: 16px; line-height: 1.5; color: #374151;">
                        Thanks for creating your {{ config('app.name') }} account. Please confirm your email address so we know it really is you.
                     </p>
                     <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
                        <tr>
                           <td>
                              <a href="{{ $url }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #16a34a; color: #ffffff; border-radius: 6px; text-decoration: none; font-size: 15px; font-weight: 600;">
                                 Confirm Email Address
                              </a>
                           </td>
                        </tr>
                     </table>
                     <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.5; color: #6b7280;">This confirmation link expires in 5 minutes.</p>
                     <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #9ca3af;">
                        You received this email because an account was registered on {{ config('app.name') }} with this email address. If this wasn't you, you can safely ignore this message.
                     </p>
                  </td>
               </tr>
               <tr>
                  <td style="padding: 16px 32px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; border-radius: 0 0 8px 8px;">
                     <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9ca3af;">
                        {{ config('mail.from.name') }} &middot; {{ config('mail.from.address') }}
                     </p>
                  </td>
               </tr>
            </table>
         </td>
      </tr>
   </table>
</body>
</html>