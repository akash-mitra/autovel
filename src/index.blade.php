@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title pull-left">
                        <PLACE_HOLDER_1>
                    </h3>
                    <button class="btn btn-default pull-right hide" id="btnNewResource"></button>
                    <div class="clearfix"></div>
                </div>

                <div class="panel-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- You are logged in! -->
                    <div id="index-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="spaModal" tabindex="-1" role="dialog" aria-labelledby="spaModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="spaModalLabel">Resource</h4>
      </div>
      <div class="modal-body" id="spaModalBody">Please wait...
      </div>
      <div class="modal-footer" id="spaModalFooter">
        <button type="button" class="btn btn-default pull-left" id="btnClose" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger pull-left" id="btnSecondaryAction" data-operation="edit">Edit</button>
        <button type="button" class="btn btn-primary" id="btnPrimaryAction" data-operation="new"></button>
        <input type="hidden" name="id" value="" id="id" />
      </div>
    </div>
  </div>
</div>
@endsection
@section('content.script')
<script>
    $(document).ready (function () {
        $(function() {
            $("#index-table").spartacus(
                "<PLACE_HOLDER_2>", 
                {
                    "exclusions": [ 'created_at', 'updated_at', "id"],
                    "<PLACE_HOLDER_3>"
                }
            );
        });
    });
</script>
@endsection
