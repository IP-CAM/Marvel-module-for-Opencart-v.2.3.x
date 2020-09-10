<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-pp-layout" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <?php if ($ready_to_auth) { ?>
        <a href="<?php echo $auth; ?>" data-toggle="tooltip" title="<?php echo $button_auth; ?>" class="btn btn-success"><i class="fa fa-sign-in"></i></a>
        <?php } ?>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($success) { ?>
    <div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($warning) { ?>
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-pp-layout" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="marvel_status" id="input-status" class="form-control">
                <option value="0"><?php echo $text_disabled; ?></option>
                <option value="1"<?php if ($marvel_status) { ?> selected="selected"<?php } ?>><?php echo $text_enabled; ?></option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-username"><?php echo $entry_username; ?></label>
            <div class="col-sm-10">
              <input type="text" name="marvel_username" value="<?php echo $marvel_username; ?>" placeholder="<?php echo $entry_username; ?>" id="input-username" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-password"><?php echo $entry_password; ?></label>
            <div class="col-sm-10">
              <input type="text" name="marvel_password" value="<?php echo $marvel_password; ?>" placeholder="<?php echo $entry_password; ?>" id="input-password" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status_imported"><?php echo $entry_status_imported; ?></label>
            <div class="col-sm-10">
              <select name="marvel_status_imported" id="input-status_imported" class="form-control">
                <option value="0"><?php echo $text_disabled; ?></option>
                <option value="1"<?php if ($marvel_status_imported) { ?> selected="selected"<?php } ?>><?php echo $text_enabled; ?></option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-local_category"><?php echo $entry_local_category; ?></label>
            <div class="col-sm-10">
              <select name="marvel_local_category_id" id="input-local_category" class="form-control">
                <option value="0" selected="selected"><?php echo $text_none; ?></option>
                <?php foreach($local_category_list as $category) { ?>
                <option value="<?php echo $category['category_id']; ?>"<?php if($category['category_id'] == $marvel_local_category_id) { ?> selected="selected"<?php } ?>><?php echo $category['name']; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-remote_category"><?php echo $entry_remote_category; ?></label>
            <div class="col-sm-10">
              <select name="marvel_remote_category[]" id="input-remote_category" class="form-control" style="height: 200px;" multiple>
                <?php foreach($remote_category_list as $category) { ?>
                <option value="<?php echo $category['id']; ?>"<?php if (in_array($category['id'], $marvel_remote_category)) { ?> selected="selected"<?php } ?>><?php echo $category['name']; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-import_attributes"><?php echo $entry_import_attributes; ?></label>
            <div class="col-sm-10">
              <select name="marvel_import_attributes" id="input-import_attributes" class="form-control">
                <option value="0"><?php echo $text_disabled; ?></option>
                <option value="1"<?php if ($marvel_import_attributes) { ?> selected="selected"<?php } ?>><?php echo $text_enabled; ?></option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-attribute_group"><?php echo $entry_attribute_group; ?></label>
            <div class="col-sm-10">
              <select name="marvel_attribute_group_id" id="input-attribute_group" class="form-control">
                <option value="0" selected="selected"><?php echo $text_none; ?></option>
                <?php foreach($attribute_group_list as $attribute_group) { ?>
                <option value="<?php echo $attribute_group['attribute_group_id']; ?>"<?php if($attribute_group['attribute_group_id'] == $marvel_attribute_group_id) { ?> selected="selected"<?php } ?>><?php echo $attribute_group['name']; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-image_count"><?php echo $entry_image_count; ?></label>
            <div class="col-sm-10">
              <input type="number" name="marvel_image_count" value="<?php echo $marvel_image_count; ?>" placeholder="<?php echo $entry_image_count; ?>" id="input-image_count" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-caching"><?php echo $entry_caching; ?></label>
            <div class="col-sm-10">
              <select name="marvel_caching" id="input-caching" class="form-control">
                <option value="0"><?php echo $text_disabled; ?></option>
                <option value="1"<?php if ($marvel_caching) { ?> selected="selected"<?php } ?>><?php echo $text_enabled; ?></option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-logs"><?php echo $entry_logs; ?></label>
            <div class="col-sm-10">
              <textarea id="input-logs" class="form-control" style="height: 200px;" readonly><?php echo $logs; ?></textarea>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>