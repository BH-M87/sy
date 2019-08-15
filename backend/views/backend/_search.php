<div class="ibox-content">
    <form role="form" class="form-inline" method="post" action="/backend/application">
        <div class="form-group">
            <select id="company-select" class="form-control select2" name="company_name">
                <option value="1" selected disabled style="display: none;">请选择企业</option>
                <?php
                foreach ($company as $v) {
                    ?>
                    <option><?= $v?></option>
                    <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <input type="text" placeholder="微应用Id" id="agent_id" name="agent_id" class="form-control">
        </div>
        <button class="btn btn-white" type="submit">搜索</button>
        <button class="btn btn-white" id="reset-btn" type="reset">重置</button>
    </form>
</div>

<script>
    var companySel = $('#company-select').select2();
    var companyName = "<?= $company_name?>";
    var agent_id= "<?= $agent_id?>";

    if (companyName) {
        companySel.val(companyName).trigger("change");
    }
    if (agent_id) {
        $("#agent_id").val(agent_id);
    }

    $("#reset-btn").click(function () {
        companySel.val(1).trigger("change");
    });
</script>