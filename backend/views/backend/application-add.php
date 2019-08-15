<?php
?>
<!-- Select2 -->
<link rel="stylesheet" href="/bower_components/select2/dist/css/select2.min.css">
<!-- Google Font -->
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

<!-- jQuery 3 -->
<script src="/bower_components/jquery/dist/jquery.min.js"></script>
<!-- Select2 -->
<script src="/bower_components/select2/dist/js/select2.full.min.js"></script>
<!-- Main content -->
<div class="col-md-8">
    <div class="box box-info">
        <form class="form-horizontal" id="agent-add-form" method="post" action = "/backend/application-add">
            <div class="box-header with-border">
                <h3 class="box-title">开通微应用</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-2 control-label">企业</label>
                    <div class="col-sm-8">
                        <select id="company-select" class="form-control select2" name="corp_id">
                            <option value="1" selected disabled style="display: none;">请选择企业</option>
                            <?php
                            foreach ($company as $v) {
                                ?>
                                <option value= "<?= $v['corp_id']?>"><?= $v['name']?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">agent_id</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="agent_id" name="agent_id"  placeholder="agent_id">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">app_key</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="app_key" name="app_key"  placeholder="app_key">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">app_secret</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="app_secret" name="app_secret"  placeholder="app_secret">
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <label for="inputPassword3" class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                    <button type="button" onclick="javascript :history.back(-1);" class="btn btn-primary">返回</button>
                    <button type="submit" id="sub-btn" class="btn btn-primary">保存</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $("#sub-btn").click(function () {
        var corp_id = $("#company-select").val();
        if (!corp_id) {
            alert("请选择企业");
            return false;
        }
        var agent_id = $("#agent_id").val();
        if (!agent_id) {
            alert("请输入微应用id");
            return false;
        }
        var app_key = $("#app_key").val();
        if (!app_key) {
            alert("请输入微应用app_key");
            return false;
        }
        var app_secret = $("#app_secret").val();
        if (!app_secret) {
            alert("请输入微应用app_secret");
            return false;
        }
        $("#agent-add-form").submit();
    });


</script>