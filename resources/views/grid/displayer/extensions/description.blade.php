<style>
    .btn-action {
        min-width: 80px;
    }

    .hover-display {
        display: none;
    }

    table tbody tr:hover .hover-display {
        display: inline;
    }
</style>
<ul class="list-inline">
    @if($row->version && empty($row->new_version))
        @if($enabled)
            <li class="list-inline-item">{!! $disableAction !!}</li>
        @else
            <li class="list-inline-item">{!! $enableAction !!}</li>
        @endif
        @if($settingAction)
            <li class="list-inline-item">{!! $settingAction !!}</li>
        @endif
    
    @else
        <li class="list-inline-item">{!! $updateAction !!}</li>
        
        @if($settingAction && $row->new_version)
            <li class="list-inline-item">{!! $settingAction !!}</li>
        @endif
    
    @endif
    @if($row->version && $uninstallAction)
        <li class="list-inline-item hover-display">
            {!! $uninstallAction !!}
        </li>
    @endif
</ul>