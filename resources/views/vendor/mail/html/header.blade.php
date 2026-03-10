@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<img src="{{ asset('logo/logo.png') }}" class="logo" alt="{{ config('app.name') }}" style="max-width: 200px; height: auto;">
@endif
</a>
</td>
</tr>
