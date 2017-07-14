<!-- Modal -->
<div id="folder-picker-dialog" class="modal fade" role="dialog">
  <div class="diqa-import-modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">{{wfMessage('diqa-import-crawling-folder-dialog-header')->text()}}</h4>
      </div>
      <div class="modal-body">
       <p>{{wfMessage('diqa-import-crawling-folder-dialog-description')->text()}}
       @include('specials.general.import-special-js-button', ['command' => 'diqa-import-unfold' ])
       @include('specials.general.import-special-js-button', ['command' => 'diqa-import-fold' ])
       </p>
       <div id="tree"></div>
            
      </div>
      <div class="modal-footer">
        <button type="button" action="ok" class="btn btn-default">OK</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">{{wfMessage('diqa-cancel-button')->text()}}</button>
      </div>
    </div>

  </div>
</div>
<script>
window.DIQA = window.DIQA || {};
window.DIQA.IMPORT = window.DIQA.IMPORT || {};
window.DIQA.IMPORT.treeContent = {!!$tree!!};

</script>