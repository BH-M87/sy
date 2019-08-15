<div class="ibox-content">
    <form role="form" class="form-inline" method="post" action="/supplier/communitys">
        <div class="form-group">
            <select id="supplier-select" class="form-control select2" name="supplier_name">
                <option value="1" selected disabled style="display: none;">请选择供应商</option>
                <?php
                foreach ($suppliers as $v) {
                    ?>
                    <option><?= $v?></option>
                    <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <select id="community-select" class="form-control select2" name="community_name" >
                <option value="" selected disabled style="display: none;">请选择小区</option>
                <?php
                foreach ($communitys as $v) {
                    ?>
                    <option><?= $v?></option>
                    <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <input type="text" placeholder="授权码" id="auth_code" name="auth_code" class="form-control">
        </div>
        <div class="form-group">
            <select class="form-control m-b" name="supplier_type" id="supplier_type">
                <option value="">接入类型</option>
                <option value="1">道闸</option>
                <option value="2">门禁</option>
            </select>
        </div>
        <button class="btn btn-white" type="submit">搜索</button>
        <button class="btn btn-white" id="reset-btn" type="reset">重置</button>
    </form>
</div>

<script>
    var supplierSel = $('#supplier-select').select2();
    var communitySel = $('#community-select').select2();
    var supplierName = "<?= $supplierName?>";
    var communityName = "<?= $communityName?>";
    var supplierType= "<?= $supplierType?>";
    var authCode = "<?= $authCode?>";

    if (supplierName) {
        supplierSel.val(supplierName).trigger("change");
    }
    if (communityName) {
        communitySel.val(communityName).trigger("change");
    }
    if (authCode) {
        $("#auth_code").val(authCode);
    }
    if (supplierType) {
        $("#supplier_type").val(supplierType);
    }

    $("#reset-btn").click(function () {
        supplierSel.val(1).trigger("change");
        communitySel.val(1).trigger("change");
    });
</script>