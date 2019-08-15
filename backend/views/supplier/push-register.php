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
        <form class="form-horizontal" id="community-register-form" method="post" action = "/supplier/push-register">
            <div class="box-header with-border">
                <h3 class="box-title">推送配置</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-4 control-label">供应商</label>
                    <div class="col-sm-8">
                        <select id="supplier-select" class="form-control select2" name="supplier_name" disabled="true">
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
                        <select id="community-select" class="form-control select2" name="community_name" disabled="true">
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
                    <label for="inputPassword3" class="col-sm-4 control-label">回调地址</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" value="<?= $pushConfigData['request_url'] ? $pushConfigData['request_url'] : ''?>" id="request_url" name="request_url"  placeholder="回调地址">
                        <span class="help-block m-b-none">请输入正确的url地址，如：http://api.elive99.com/site/test</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-4 control-label">解密秘钥</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" value="<?= $pushConfigData['aes_key'] ? $pushConfigData['aes_key'] : ''?>" id="aes_key" name="aes_key"  placeholder="解密秘钥">
                        <span class="help-block m-b-none">请输入6位随机数，从a-zA-Z0-9里取6位</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-4 control-label">回调接口名称</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" value="<?= $pushConfigData['call_back_tag'] ? $pushConfigData['call_back_tag'] : ''?>" id="call_back_tag" name="call_back_tag"  placeholder="回调接口名称">
                        <span class="help-block m-b-none">接口名称参见门禁，道闸第三方对接技术方案文档中回调接口名称列表，如：communityAdd,buildingAdd,buildingDelete，默认不填为接入所有接口</span>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <label for="inputPassword3" class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                    <button type="button" onclick="javascript :history.back(-1);" class="btn btn-primary">返回</button>
                    <input type="hidden" id="id" name="id" value="<?= $checkedCommunity['id']?>" />
                    <button type="submit" id="sub-btn" class="btn btn-primary">保存</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    var supplierSel = $('#supplier-select').select2();
    var communitySel = $('#community-select').select2();
    var supplierSelectedId = "<?= $checkedCommunity['supplier_id']?>";
    var communitySelectedId = "<?= $checkedCommunity['community_id']?>";
    if (supplierSelectedId) {
        supplierSel.val(supplierSelectedId).trigger("change");
    }
    if (communitySelectedId) {
        communitySel.val(communitySelectedId).trigger("change");
    }

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
        var requestUrl = $('#request_url').val();
        if(requestUrl == null){
            alert("请填写回调地址!");
            return false;
        }
        var aesKey = $('#aes_key').val();
        if(aesKey == null){
            alert("请填写解密秘钥!");
            return false;
        }
        $("#community-register-form").submit();
    });


</script>