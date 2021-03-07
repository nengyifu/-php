<?php
	//起始点
	checkPT1:
	// 用session保存缓存，点击提交后内存会被清除
	session_start();
	//清除缓存重新开始
	if(isset($_POST["submit0"])){
		// reset 按钮0
		$_POST["submit0"] = NULL;
		// destroy cache
		session_destroy();
		// return to checkpoint1
		goto checkPT1;
	}
	//下面是htlm部分
?>

<!DOCTYPE html>
	<html lang="en" >
		<meta charset="utf-8">
		<head>
	    	<title>璀陆云 阿尔法TEM 瞬变电磁数据处理</title>
		</head>

		<body>
			<header>
					<h1>璀陆云 瞬变电磁数据处理</h1>
				</header>
		<form method="post">
			<input type="submit" name='submit0' value="清除缓存并重新开始">
		</form>
		<header>
				<h2>步骤1：数据上传</h2>
		</header>
<?php
	// 步骤1，上传 txt 文件
	if(!isset($_SESSION['upload'])){
?>
	<form method="post" enctype="multipart/form-data" >
	<input type="file" name='name' />
	<input type="submit" name='submit1' value="上传"/>
	</form>
<?php
	}
	// 如果uplaod这个步骤没做，检查上传（submit1）是否被按，按了就上传文件，并且check 上传这个步骤，下文不再显示上传
	if(!isset($_SESSION['upload'])){
		if(isset($_POST['submit1'])){
			// check the box
			$_SESSION['upload'] = true;
			// load data
			$data=$_FILES['name'];
			echo "文件名<b>:</b> ".$data['name']. " 文件上传成功！";
			move_uploaded_file($data['tmp_name'],"uploads/".$data['name']);

			// read data
			$file_path = "uploads/".$data['name'] ;
			$fp = fopen ( $file_path , "r" );		// r for read only
			$r12 = explode("	",fgets($fp));
			$_SESSION['noPts'] = $r12[0];
			$_SESSION['noWindows'] = $r12[1];
			fclose($fp);
		}
		//如果上传这个步骤已完成，直接显示上传文件的info
	}else{
				echo "桩号数: ".$_SESSION['noPts']. ".	窗口数: ".$_SESSION['noWindows'];
		}?>
		<header>
				<h2>步骤2：反演深度</h2>
		</header>
		<?php
		// 如果步骤2没有完成 and 步骤1完成了， 显示这个表格，按了提交 （submit2） 后也不会显示
		if(!isset($_SESSION['DOI'])&&isset($_SESSION['upload'])&&!isset($_POST['submit2'])){
		?>
		<form method="post">
		反演深度:
		<input type="number" name='DOI' value= 99 /><br />
		<input type="submit" name = 'submit2' value="确认">
		</form>
		<?php
	}
	// 按了提交按钮，储存信息
	if(isset($_POST['submit2'])){
		$_SESSION['DOI'] = $_POST['DOI'];

	// 如果信息已经储存了，打印它
	}
	if(isset($_SESSION['DOI'])){
		echo "反演深度: ". $_SESSION['DOI']. "米";
	}

	
	if(isset($_SESSION['DOI'])&&!isset($_POST['submit3'])){
		if(isset($_SESSION['showModel'])){
			$_POST['rhoMin'] = $_SESSION['rhoMin'];
			$_POST['rhoMax'] = $_SESSION['rhoMax'];
			$_POST['noLayer'] = $_SESSION['noLayer'];
			$_POST['fb'] = $_SESSION['fb'];
			$_POST['q'] = $_SESSION['q'];
		}else{
			$_POST['rhoMin'] = 27;
			$_POST['rhoMax'] = ceil($_SESSION['DOI']*pi());
			$_POST['noLayer'] = floor(($_SESSION['DOI'] - 6)/33 +6);
			$_POST['fb'] = .6;
			$_POST['q'] = 1.234;
		}
	}
?>
<header>
		<h2>步骤3：模型设置</h2>
</header>
<?php
if(isset($_POST['submit5'])||isset($_SESSION['model'])){
	$_SESSION['model'] = true;
	for($i=0;$i<$_SESSION['noLayer']-1;$i++){
		echo intval($_SESSION['rhos'][$i])."	".intval($_SESSION['depths'][$i])."<br>";
	}
	echo intval($_SESSION['rhos'][$i])."<br>";
}
	if(isset($_SESSION['DOI'])&&!isset($_SESSION['model'])){
?>

<form method="post">
初始模型:<br>
覆盖层层电阻率 (欧姆*米) <input type="number" name='rhoMin' value= <?php echo $_POST['rhoMin']?> /><br />
地核电阻率 (欧姆*米) <input type="number" name='rhoMax' value= <?php echo $_POST['rhoMax']?> /><br />
层数 <input type="number" name='noLayer' value= <?php echo $_POST['noLayer']?> /><br />
搜索范围:<input type="decimals" name='fb' value=<?php echo $_POST['fb']?> /><br />
层厚变化率:<input type="decimals" name='q' value=<?php echo $_POST['q']?> /><br />
<input type="submit" name = 'submit3' value="生成模型">
</form>
<?php
}else if(isset($_SESSION['DOI'])&&isset($_SESSION['model'])){
echo "<br>"."覆盖层层电阻率 (欧姆*米): ". $_SESSION['rhoMin']. "<br>";
echo "地核电阻率 (欧姆*米): ". $_SESSION['rhoMax']. "<br>";
echo "层数: ".$_SESSION['noLayer']. "<br>";
echo "搜索范围: " .$_SESSION['fb']."<br>";
echo "层厚变化率: " .$_SESSION['fb'];
}
if(isset($_POST['submit3'])){
	$_SESSION['showModel'] = true;
	$_SESSION['rhoMin'] = $_POST['rhoMin'];
	$_SESSION['rhoMax'] = $_POST['rhoMax'];
	$_SESSION['noLayer'] = $_POST['noLayer'];
	$_SESSION['fb'] = $_POST['fb'];
	$_SESSION['q'] = $_POST['q'];

 $V = pow($_SESSION['rhoMax']/ $_SESSION['rhoMin'],1/ ($_SESSION['noLayer']-1));
 $w = $_SESSION['DOI']*(1-$_SESSION['q'])/(1-pow($_SESSION['q'],$_SESSION['noLayer']-1));
 $rhos = array();
 $rhoBD0 = array();
 $rhoBD1 = array();

 $depths = array();
 $depthBD0 = array();
 $depthBD1 = array();
 for($i = 0; $i < $_SESSION['noLayer']; $i++){
	 $rhos[$i] = $_SESSION['rhoMin']*pow($V,$i);
	 $rhoBD0[$i] = $rhos[$i]*(1-$_SESSION['fb']);
	 $rhoBD1[$i] = $rhos[$i]*(1+$_SESSION['fb']);
 }
 for($i = 0; $i < $_SESSION['noLayer']-1; $i++){
	$depths[$i] = $w*pow($_SESSION['q'],$i);
	$depthBD0[$i] = $depths[$i]*(1-$_SESSION['fb']);
	$depthBD1[$i] = $depths[$i]*(1+$_SESSION['fb']);
 }
	$_SESSION['rhos'] = $rhos;
	$_SESSION['rhoBD0'] = $rhoBD0;
	$_SESSION['rhoBD1'] = $rhoBD1;
	$_SESSION['depths'] = $depths;
	$_SESSION['depthBD0'] = $depthBD0;
	$_SESSION['depthBD1'] = $depthBD1;
}

if(isset($_POST['submit4'])){
	for($i=0;$i<$_SESSION['noLayer']-1;$i++){
		$_SESSION['rhos'][$i]=$_POST[$i*2];
		$_SESSION['rhoBD0'][$i] = $_POST[$i*2]*(1-$_SESSION['fb']);
		$_SESSION['rhoBD1'][$i] = $_POST[$i*2]*(1+$_SESSION['fb']);

		$_SESSION['depths'][$i]=$_POST[$i*2+1];
		$_SESSION['depthBD0'][$i] = $_POST[$i*2+1]*(1-$_SESSION['fb']);
		$_SESSION['depthBD1'][$i] = $_POST[$i*2+1]*(1+$_SESSION['fb']);
	}

	$_SESSION['rhos'][$_SESSION['noLayer']-1]=$_POST[($_SESSION['noLayer']-1)*2];
	$_SESSION['rhoBD0'][$_SESSION['noLayer']-1] = $_POST[($_SESSION['noLayer']-1)*2]*(1-$_SESSION['fb']);
	$_SESSION['rhoBD1'][$_SESSION['noLayer']-1] = $_POST[($_SESSION['noLayer']-1)*2]*(1+$_SESSION['fb']);
}

if(isset($_SESSION['showModel'])){
	$_POST['rhos'] = $_SESSION['rhos'];
	$_POST['rhoBD0'] = $_SESSION['rhoBD0'];
	$_POST['rhoBD1'] = $_SESSION['rhoBD1'];
	$_POST['depths'] = $_SESSION['depths'];
	$_POST['depthBD0'] = $_SESSION['depthBD0'];
	$_POST['depthBD1'] = $_SESSION['depthBD1'];
}
	//echo "电阻率 (欧米*米)		电阻率搜索范围			层厚度(米)		层厚度搜索范围";
	if(isset($_SESSION['showModel'])&&!isset($_SESSION['model'])){

		?><form method="post"><?php
		for($i=0;$i<$_SESSION['noLayer']-1;$i++){

			?>
			<input type="decimals" name="<?php echo $i*2?>" value= <?php echo intval($_POST['rhos'][$i])?>>
			<input type="decimals" name="<?php echo $i*2+1?>"value= <?php echo intval($_POST['depths'][$i])?> >
			[<?php echo intval($_POST['rhoBD0'][$i])?>,<?php echo intval($_POST['rhoBD1'][$i])?>],
			[<?php echo intval($_POST['depthBD0'][$i])?>,<?php echo intval($_POST['depthBD1'][$i])?>]<br>
			<?php
		}
		?>
		<input type="decimals" name="<?php echo ($_SESSION['noLayer']-1)*2?>" value= <?php echo $_POST['rhos'][$_SESSION['noLayer']-1]?>>
		[<?php echo intval($_POST['rhoBD0'][$_SESSION['noLayer']-1])?>,<?php echo intval($_POST['rhoBD1'][$_SESSION['noLayer']-1])?>]
		<?php

		?>
		<br>
		<input type="submit" name = 'submit4' value="更新模型">
		<input type="submit" name = 'submit5' value="确认模型">

		<?php
	}

	?>
	<header>
			<h2>步骤4：参数设置</h2>
	</header>
	<?php

	if(isset($_POST['submit6'])){
		$_SESSION['noModels'] = $_POST['noModels'];
		$_SESSION['maxIt'] = $_POST['maxIt'];
		$_SESSION['autostop'] = $_POST['autostop'];
		$_SESSION['POW'] = $_POST['POW'];
		$_SESSION['ZIP'] = $_POST['ZIP'];
		$_SESSION['weightOccam'] = $_POST['weightOccam'];
		$_SESSION['powerRidge'] = $_POST['powerRidge'];
		$_SESSION['fc'] = $_POST['fc'];
		$_SESSION['parameter'] = true;
	}

	if(isset($_SESSION['parameter'])){
		echo "模型基数: ".$_SESSION['noModels']."<br>";
		echo "最大迭代次数: ".$_SESSION['maxIt']."<br>";
		echo "误差无减小自动停止迭代次数: ".$_SESSION['autostop']."<br>";
		echo "异常/分层参数:  ".$_SESSION['POW']."<br>";
		echo "数据滤波/反滤波系数:  ".$_SESSION['ZIP']."<br>";
		echo "平滑度权重: ".$_SESSION['weightOccam']."<br>";
		echo "模型控制权重(异常/分层): ".$_SESSION['powerRidge']."<br>";
		echo "地质变化大小: ".$_SESSION['fc']."<br>";
	}

	if(isset($_SESSION['model'])&&!isset($_SESSION['parameter'])){
	?>

	<form form method="post">
	模型基数: 									<input type="number" name='noModels' value=3 /><br />
	最大迭代次数: 							<input type="number" name='maxIt' value=17 /><br />
	误差无减小自动停止迭代次数: 	<input type="number" name='autostop' value=9 /><br />
	异常/分层参数: 							<input type="decimals" name='POW' value=.007 /><br />
	数据滤波/反滤波系数: 				<input type="decimals" name='ZIP' value=1.5 /><br />
	平滑度权重: 								 <input type="decimals" name='weightOccam' value=.02 /><br />
	模型控制权重(异常/分层): 		 <input type="decimals" name='powerRidge' value=.08 /><br />
	地质变化大小: 							<input type="decimals" name='fc' value=.1 /><br />

	<input type="submit" name = 'submit6' value="确认">
	</form>
	<?php
}

?>

<header>
    <h2>步骤5：数据类型</h2>
</header>

<?php
	if(isset($_POST['land'])||isset($_POST['air'])||isset($_POST['tunnel'])){
			if(isset($_POST['land'])){
				$_SESSION['mode'] = "陆地模式";
			}else if(isset($_POST['air'])){
				$_SESSION['mode'] = "航空模式";
			}else{
				$_SESSION['mode'] = "隧道扇形扫描";
			}
	}

if(isset($_SESSION['mode'])){
	echo $_SESSION['mode'];
}

	if(isset($_SESSION['parameter'])){
		?>
		<form form method="post">

		<input type="submit" name = 'land' value="陆地">
		<input type="submit" name = 'air' value="航空">
		<input type="submit" name = 'tuunel' value="隧道扇形扫描">
		</form>
		<?php
	}
?>

	</body>
	    <footer><p>版权所有 重庆璀陆探测技术有限公司 © Copyright 2020. All Rights Reserved.</p></footer>
</html>
