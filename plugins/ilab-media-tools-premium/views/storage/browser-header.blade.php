<?php
$pathParts = (empty($path)) ? [] : explode('/', trim($path, '/'));
$previousPart = '';
?>
<div class="ilab-storage-browser-header">
    <ul>
        <li><a href="#" data-file-type="dir" data-key="">{{$bucketName}}</a></li>
        @foreach($pathParts as $part)
		    <?php $previousPart = $previousPart . $part . '/' ?>
            <li><a href="#" data-file-type="dir" data-key="{{$previousPart}}">{{$part}}</a></li>
        @endforeach
    </ul>
</div>