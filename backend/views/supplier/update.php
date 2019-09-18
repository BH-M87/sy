<?php
?>
<div class="col-md-8">
    <div class="box box-info">
        <form class="form-horizontal" method="post" action = "/supplier/update">
            <div class="box-header with-border">
                <h3 class="box-title">编辑供应商</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="inputEmail3" class="col-sm-2 control-label">供应商名称</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="name" value="<?= $model['name']?>" placeholder="供应商名称">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">联系人</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="contactor" name="contactor" value="<?= $model['contactor']?>" placeholder="联系人">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword3" class="col-sm-2 control-label">联系电话</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="mobile" name="mobile" value="<?= $model['mobile']?>"  placeholder="联系电话">
                    </div>
                </div>
<!--                <div class="form-group">-->
<!--                    <label for="inputPassword3" class="col-sm-2 control-label">供应商类型</label>-->
<!--                    <div class="col-sm-10">-->
<!--                        <label>-->
<!--                            <input type="radio" name="r1" class="minimal">门禁-->
<!--                        </label>-->
<!--                        <label>-->
<!--                            <input type="radio" name="r1" class="minimal">道闸-->
<!--                        </label>-->
<!--                    </div>-->
<!--                </div>-->

            </div>

            <div class="box-footer">
                <button type="button" onclick="javascript :history.back(-1);" class="btn btn-primary">返回</button>
                <input type="hidden" id="id" name="id" value="<?= $model['id']?>" />
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>
