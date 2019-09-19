<?php
?>
<div class="col-md-8">
    <div class="box box-info">
        <form class="form-horizontal" method="post" action = "/backend/add">
            <div class="box-header with-border">
                <h3 class="box-title">添加企业</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-2 control-label">企业名称</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="name" placeholder="供应商名称">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">corp_id</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="corp_id" name="corp_id"  placeholder="企业corp_id">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-2 control-label">关联公司</label>
                    <div class="col-sm-8">
                        <select id="company-select" class="form-control select2" name="company_id">
                            <option value="1" selected disabled style="display: none;">请选择邻易联物业公司</option>
                            <?php
                            foreach ($companyList as $v) {
                                ?>
                                <option value= "<?= $v['id']?>"><?= $v['name']?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="button" onclick="javascript :history.back(-1);" class="btn btn-primary">返回</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>
