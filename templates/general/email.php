<!-- email sent to new users upon registration -->

<div style="background-color:#ededeb;">
<center>
<table width="567" cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, Helvetica, sans-serif;margin-top:30px;margin-bottom:30px;">
	<tr><td height="30" style="background-color:#ededeb;"></td></tr>
	<tr><td height="3" style="background-color:#605f5f;"></td></tr>
	<tr>
		<td height="57" style="background-color:#383838;padding:0 20px;" >
			<h1 style=" font-size: 20px; font-weight: bold;">
			<a href="{siteurl}" style="text-decoration: none; color: #ffffff;">
			&#x2709; &nbsp;{sitename}</a></h1>
		</td>
	</tr>
	<tr><td height="15" style="background-color:#EDEDEB;"></td></tr>
	<tr>
		<td style="background-color:#ffffff;border:1px solid #d7d7d7;padding:20px;">
			<hr style="border: 0;color: #ededeb;background-color:#ededeb;height:1px;width:100%;text-align: left;">
			<div style="margin:20px auto;font-size:13px;line-height:18px;color:#444444;">
				<!-- email contents here -->
				{email_contents}
			</div>
		</td>
	</tr>
	<tr>
		<td style="background-color:#ffffff;border:1px solid #d7d7d7;padding:0;">
			<div style="margin:0 20px 5px;font-size:11px;line-height:15px;color:#444444;">
				This email was sent to {userfullname} ({useremail}).
				<br />
				If you do not wish to receive these emails from {sitename}, you can <a href='{unsubscribeurl}'>unsubscribe</a> here.
				<br />Copyright (c) {year} {sitename}
			</div>
		</td>
	</tr>
	<tr><td height="3" style="background-color:#e0dfdf;"></td></tr>
	<tr><td height="30" style="background-color:#ededeb;"></td></tr>
</table>
</center>
</div>