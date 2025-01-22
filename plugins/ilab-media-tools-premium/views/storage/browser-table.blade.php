<?php /** @var \MediaCloud\Plugin\Cloud\Storage\StorageFile[] $files */ ?>
<?php /** @var bool $hideCheckBoxes */ ?>
<?php /** @var bool $hideActions */ ?>
<table>
    <thead>
    <tr>
        @if(empty($hideCheckBoxes))
        <th class="checkbox"><input type="checkbox"></th>
        @endif
        <th>Name</th>
        <th>Last Modified</th>
        <th>Size</th>
        @if(empty($hideActions))
        <th class="actions"></th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach($files as $file)
        <tr data-file-type="{{strtolower($file->type())}}" data-key="{{$file->key()}}" @if($file->name() != '..')data-key="{{$file->key()}}"@endif>
            @if(empty($hideCheckBoxes))
            <td class="checkbox">
                @if($file->name() != '..')
                <input type="checkbox">
                @endif
            </td>
            @endif
            <td class="entry">
                <img class="loader" src="{{admin_url('images/spinner.gif')}}">
                @if($file->name() == '..')

                <span class="icon-up"></span>
                @else
                <span class="icon-{{strtolower($file->type())}}"></span>
                @endif
                {!! $file->name() !!}
            </td>
            <td>{{$file->dateString()}}</td>
            <td>{{$file->sizeString()}}</td>
            @if(empty($hideActions))
            <td class="actions">
                @if($file->type() == 'FILE')
                    <a href="{{(empty($file->url())) ? '#' : $file->url()}}" class="ilab-browser-action-view button button-small {{(empty($file->url())) ? 'disabled' : ''}}" target="_blank">View</a>
                    @if($allowDeleting)
                    <a href="#" class="ilab-browser-action-delete button button-small button-delete">Delete</a>
                    @endif
                @endif
            </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>