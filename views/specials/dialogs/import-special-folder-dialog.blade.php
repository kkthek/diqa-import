<!-- Modal -->
<div id="folder-picker-dialog" class="modal fade" role="dialog">
  <div class="diqa-import-modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">{{wfMessage('diqa-import-crawling-folder-dialog-header', $attribute)->text()}}</h4>
      </div>
      <div class="modal-body">
       <p>{{wfMessage('diqa-import-crawling-folder-dialog-description')->text()}}
       @include('specials.general.import-special-js-button', ['command' => 'diqa-import-unfold' ])
       @include('specials.general.import-special-js-button', ['command' => 'diqa-import-fold' ])
       <a href="{{wfDIQAURL('Special:DIQAImport')}}" target="_blank">[{{wfMessage('diqa-import-open-crawl-site')->text()}}]</a>
       </p>
       <div id="tree"></div>
       <div id="option-pane">
           <div><h4>{{wfMessage('diqa-import-legende')->text()}}</h2></div>	
           <div class="option-pane-row"><span class="diqa-import-proposal-folder">{{wfMessage('diqa-import-proposals')->text()}}</span></div>
	       <div class="option-pane-row"><span class="diqa-import-selected-folder">{{wfMessage('diqa-import-selected-folders')->text()}}</span></div>
       	   <div><h4>{{wfMessage('diqa-import-actions')->text()}}</h2></div>	
	       <div class="option-pane-row"><input type="checkbox" action="show-proposals" checked="true"/>&nbsp;{{wfMessage('diqa-import-show-proposals')->text()}}</div>
	       <div class="option-pane-row"><button type="button" action="select-proposals" class="btn btn-default">{{wfMessage('diqa-import-select-proposals')->text()}}</button></div>
       </div>     
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