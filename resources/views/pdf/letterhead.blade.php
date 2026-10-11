<style>
@page { margin-top: 105pt; margin-bottom: 92pt; }
.document-letterhead-header { position: fixed; top: -96pt; left: 0; right: 0; height: 88pt; text-align: center; overflow: hidden; }
.document-letterhead-footer { position: fixed; bottom: -84pt; left: 0; right: 0; height: 78pt; text-align: center; overflow: hidden; }
.document-letterhead-header img, .document-letterhead-footer img { width: 100%; height: 100%; }
</style>
@if($documentBrandHeader)<div class="document-letterhead-header"><img src="{{ $documentBrandHeader }}"></div>@endif
@if($documentBrandFooter)<div class="document-letterhead-footer"><img src="{{ $documentBrandFooter }}"></div>@endif
