
<div id="add-tagging-rule" class="diqa-import-form" style="{{$edit ? 'display: block' : 'display: none'}}">
<form action="{{wfDIQAURL('Special:DIQAtagging')}}" method="post">
@include('specials.tagging.import-special-taggingrule-edit')
</form>
</div>