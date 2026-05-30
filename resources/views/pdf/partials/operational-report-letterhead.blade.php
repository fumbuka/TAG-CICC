<table class="letterhead">
    <tr>
        <td style="width: 18%; text-align: left;">
            @if ($motherChurchLogo)
                <img class="logo" src="{{ $motherChurchLogo }}" alt="TAG">
            @endif
        </td>
        <td class="church-name">
            <div class="h1">{{ __('messages.parent_church_name') }}</div>
            <div class="h2">{{ __('messages.local_church_name') }}</div>
            <div class="h3">{{ __('messages.church_location') }}</div>
        </td>
        <td style="width: 18%; text-align: right;">
            @if ($localChurchLogo)
                <img class="logo" src="{{ $localChurchLogo }}" alt="TAG-CICC">
            @endif
        </td>
    </tr>
</table>
