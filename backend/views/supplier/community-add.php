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
        <form class="form-horizontal" id="community-add-form" method="post" action = "/supplier/community-add">
            <div class="box-header with-border">
                <h3 class="box-title">开通小区</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-4 control-label">供应商</label>
                    <div class="col-sm-8">
                        <select id="supplier-select" class="form-control select2" name="supplier_name">
                            <option value="1" selected disabled style="display: none;">请选择供应商</option>
                            <?php
                            foreach ($suppliers as $v) {
                                ?>
                                <option value= "<?= $v['id']?>"><?= $v['name']?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-4 control-label">小区</label>
                    <div class="col-sm-8">
                        <select id="community-select" class="form-control select2" name="community_name" >
                            <option value="" selected disabled style="display: none;">请选择小区</option>
                            <?php
                            foreach ($communitys as $v) {
                                ?>
                                <option value = "<?= $v['id']?>"><?= $v['name']?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-4 control-label">供应商类型</label>
                    <div class="col-sm-8">
                        <label>
                            <input type="radio" name="supplier_type" value="2" class="minimal">门禁
                        </label>
                        <label>
                            <input type="radio" name="supplier_type" value="1" class="minimal">道闸
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-4 control-label">接入类型</label>
                    <div class="col-sm-8">
                        <label>
                            <input type="radio" name="interface_type" value="1" class="minimal">接入第三方
                        </label>
                        <label>
                            <input type="radio" name="interface_type" value="2" class="minimal">第三方接我们
                        </label>
                        <label>
                            <input type="radio" name="interface_type" value="3" class="minimal">只对接出入记录
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-4 control-label">是否开通支付宝停车缴费</label>
                    <div class="col-sm-8">
                        <label>
                            <input type="radio" name="open_alipay_parking" value="1" class="minimal">是
                        </label>
                        <label>
                            <input type="radio" name="open_alipay_parking" value="0" class="minimal">否
                        </label>
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
    var supplierSel = $('#supplier-select').select2();
    var communitySel = $('#community-select').select2();

    $("#sub-btn").click(function () {
        var supplierId = $("#supplier-select").val();
        var communityId = $("#community-select").val();

        if (!supplierId) {
            alert("请选择供应商");
            return false;
        }

        if (!communityId) {
            alert("请选择小区");
            return false;
        }
        var supplierType = $('input:radio[name="supplier_type"]:checked').val();
        if(supplierType == null){
            alert("请选择供应商类型!");
            return false;
        }
        var interfaceType = $('input:radio[name="interface_type"]:checked').val();
        if(interfaceType == null){
            alert("请选择接入类型!");
            return false;
        }
        var openAlipayParking = $('input:radio[name="open_alipay_parking"]:checked').val();
        if(openAlipayParking == null){
            alert("请选择是否开通支付宝停车缴费功能!");
            return false;
        }
        $("#community-add-form").submit();
    });


</script>