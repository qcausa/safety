<div id="ilabm-container-{{$modal_id}}" class="ilabm-backdrop ilab-add-image-size-backdrop">
    <div class="ilabm-container ilab-add-image-size-container">
        <div class="ilabm-titlebar">
            <h1>Add New Image Size</h1>
            <a title="{{__('Close')}}" href="javascript:ILabModal.cancel();" class="ilabm-modal-close">
                <span class="ilabm-modal-icon"></span>
            </a>
        </div>
        <form class="ilab-new-image-size-form" method="post" action="{{admin_url('admin.php?page=media-tools-image-sizes')}}">
            <input type="hidden" name="nonce" value="{{wp_create_nonce('add-image-size')}}">
            <div class="row"><label for="name">Name</label><input type="text" name="name" placeholder="Size Name" required></div>
            <div class="row"><label for="width">Width</label><input type="number" min="0" max="99999" placeholder="Width" name="width" required></div>
            <div class="row"><label for="height">Height</label><input type="number" min="0" max="99999" placeholder="Height" name="height" required></div>
            <div class="row"><label for="crop">Crop</label><div>@include('base/ui/checkbox', ['name' => '_crop', 'value' => false, 'description' => 'Crop Enabled', 'enabled' => true])</div></div>
            <div class="row">
                <label for="x-axis">Crop X Axis</label>
                <select name="x-axis">
                    <option value="left">Left</option>
                    <option selected value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div class="row">
                <label for="y-axis">Crop Y Axis</label>
                <select name="y-axis">
                    <option value="top">Top</option>
                    <option selected value="center">Center</option>
                    <option value="bottom">Bottom</option>
                </select>
            </div>
            <div class="row">
                <label for="privacy">Privacy</label>
                <select name="privacy">
                    <option value="inherit">Inherit</option>
                    <option value="public-read">Public</option>
                    <option value="private">Private</option>
                </select>
            </div>
            <div class="button-row">
                <input class="button button-primary" type="submit" value="Create Image Size">
            </div>
        </form>
    </div>
</div>