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
<div class="col-md-12">
    <div class="box box-info">
        <form class="form-horizontal" id="community-add-form" method="post" action = "/company-h5/bound">
            <div class="box-header with-border">
                <h3 class="box-title">绑定小区</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">公司</label>
                    <div class="col-sm-10">
                        <select id="supplier-select" class="form-control select2" name="pro_id" required>
                            <option value="1" selected disabled style="display: none;">请选择物业公司</option>
                            <?php
                            foreach ($company as $v) {
                                ?>
                                <option value= "<?= $v['id']?>"><?= $v['property_name']?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">小区ID</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="supplier_name" required name="communitys" required placeholder="(多个使用','分隔)">
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

        $("#community-add-form").submit();
    });


</script>