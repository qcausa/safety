<script>
    var mediaCloudDirectUploadSettings = {
        "driver": '{{$driver}}',
        "maxUploads": {{(int)$maxUploads}},
        "allowedMimes": {!! json_encode($allowedMimes, JSON_PRETTY_PRINT) !!},
        "wildcardUploads": {{($wildcardUploads) ? 'true' : 'false'}},
        "imageQuality": {{$imageQuality}},
        "generateThumbnails": {{($generateThumbnails) ? 'true' : 'false'}}
    };
</script>