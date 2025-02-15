<style>
    .badge {
        font-size: 8px;
        min-width: 70px;
        line-height: 1;
        text-align: center;
        vertical-align: middle;
    }

    .badge-installed {
        background: #10a94f;
        color: #ffffff;
    }

    .hover-display {
        display: none;
    }

    .ext-name:hover .hover-display {
        display: inline;
    }
</style>
<div class="pl-1" style="margin-top: 3px;">
    <ul class="list-inline">
        @if($row->alias)
            <li class="list-inline-item ext-name">
                @if($enabled)
                    <span class="text-green font-sm-3" style="padding-right: 3px;"><i class="fa fa-circle"></i></span>
                @else
                    <span class="text-red font-sm-3" style="padding-right: 3px;"><i class="fa fa-circle"></i></span>
                @endif
                <span class="font-md-2" style="margin-right:10px;">{{ $row->alias }}</span>
                <span class="font-sm-3 hover-display">{{ $value }}</span>
            </li>
        @else
            <li class="list-inline-item">
                <span class="font-md-1">{{ $packageName }}</span>
            </li>
        @endif
    </ul>
    <ul class="list-inline">
        <li class="list-inline-item">
            @if($version)
                <span class="badge badge-dark" style="font-size: 8px">Version: {{$version}}</span>
            @else
                <span class="badge badge-dark" style="font-size: 8px">Not Installed</span>
            @endif
        </li>
    </ul>
</div>
@if($row->type === Dcat\Admin\Extend\ServiceProvider::TYPE_THEME)
    <span>{{ trans('admin.theme') }}</span>
@endif

@if($row->version)
    @if($row->type === Dcat\Admin\Extend\ServiceProvider::TYPE_THEME)
        &nbsp;|&nbsp;
    @endif

@endif
