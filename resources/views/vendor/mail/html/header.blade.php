@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; font-size: 20px; font-weight: 700; color: #465fff; text-decoration: none;">
{!! $slot !!}
</a>
</td>
</tr>
