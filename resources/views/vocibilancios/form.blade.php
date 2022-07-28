
<div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
    <label for="name" class="col-md-2 control-label">Name</label>
    <div class="col-md-10">
        <input type="text" class="form-control" name="name"  id="name" required="true" placeholder="Enter name here..." value="{{ old('name', optional($vocibilancio)->name) }}"> 
        {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('tassonomia') ? 'has-error' : '' }}">
    <label for="tassonomia" class="col-md-2 control-label">Tassonomia</label>
    <div class="col-md-10">
        <input type="text" class="form-control" name="tassonomia"  id="tassonomia" required="true" placeholder="Enter tassonomia here..." value="{{ old('tassonomia', optional($vocibilancio)->tassonomia) }}">
        {!! $errors->first('tassonomia', '<p class="help-block">:message</p>') !!}
    </div>
</div>

