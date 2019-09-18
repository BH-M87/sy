<?php
?>
<div class="col-md-8">
    <div class="box box-info">
        <form class="form-horizontal" method="post" action = "/supplier/add">
            <div class="box-header with-border">
                <h3 class="box-title">添加供应商</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-2 control-label">供应商名称</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="name" placeholder="供应商名称">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">联系人</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="contactor" name="contactor"  placeholder="联系人">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">联系电话</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="mobile" name="mobile"  placeholder="联系电话">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">供应商类型</label>
                    <div class="col-sm-10">
                        <label>
                            <input type="radio" name="type" value="2" class="minimal">门禁
                        </label>
                        <label>
                            <input type="radio" name="type" value="1" class="minimal">道闸
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">供应商标识</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="supplier_name" name="supplier_name"  placeholder="供应商标识">
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
