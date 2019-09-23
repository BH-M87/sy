<?php
?>
<div class="col-md-8">
    <div class="box box-info">
        <form class="form-horizontal" method="post" action = "/index.php/property/v1/company-h5/add">
            <div class="box-header with-border">
                <h3 class="box-title">添加物业公司</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-2 control-label">企业名称</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="enterprise_name" required placeholder="企业名称">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">支付宝账号</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="supplier_name" name="alipay_account" required placeholder="支付宝账号">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">联系人</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="contactor" name="link_name" required  placeholder="联系人">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">联系电话</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="mobile" name="link_mobile" required placeholder="联系电话">
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
