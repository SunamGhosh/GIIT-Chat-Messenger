<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
} else {
    ?>
    <?php include("header.php"); ?>
    <style type="text/css">
        table {
            width: 100%;
        }

        [scope="row"] {
            text-align: center;
        }

        .content-panel.col-xs-12 p {
            margin: 0;
        }

        .press-coverage {
            background-color: #e4e4e4;
            margin-top: 20px;
            padding-bottom: 15px;
        }

        .press-coverage img {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 5px #ccc;
            display: block;
            margin: 0 auto;
            max-width: 100%;
            padding: 10px;
        }

        .media-title {
            background-color: #006699;
            color: rgb(255, 255, 255);
            font-size: 16px;
            font-weight: bold;
            padding: 12px 10px 11px;
        }

        ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .press-coverage li {
            padding: 15px 15px 0;
        }

        .page-title:after {
            display: none;
        }
    </style>
    <div class="container">
        <div id="mainContainer" class="clearfix">
            <!--<div class="col-sm-5 col-md-3">
      <?php //include("includes/sidebar.php"); ?>
    </div>
    -->
            <div class="col-sm-12 col-md-12">
                <div class="content-panel col-xs-12">
                    <?php
                    if (isset($_SESSION['s_id'])) {
                        $query = "SELECT * FROM student WHERE s_id = " . $_SESSION['s_id'];
                        $row = mysqli_query($con, $query);
                        $student = mysqli_fetch_object($row);
                        $roll = $student->s_roll_no;

                        //FETCH ALL FE STRUCTURE DETAILS
                        $query = "SELECT * FROM student_fee_option_master WHERE roll='$roll' AND fee_option_status='active'";
                        $res = mysqli_query($con, $query);
                        $no_data = false;
                        if (mysqli_num_rows($res) == 0)
                            $no_data = true;
                        else
                            $fee_master = mysqli_fetch_object($res);
                    }
                    ?>
                    <h2 class="clearfix">
                        <span class="page-title col-xs-12">
                            <?php echo $student->s_name; ?>
                            <span class="pull-right">
                                Fee Structure -
                                <?php echo $_SESSION['course']['course_name']; ?>
                            </span>
                        </span>
                    </h2>
                    <div class="col-md-12">
                        <div class="w3-row-padding">
                            <div class="w3-col l4 m4">
                                <div class="w3-padding">
                                    <?php
                                    echo ("
					<table class='w3-table w3-border' border='1'>
						<thead class='w3-dark-gray'>
							<tr>
								<th colspan='2'>
									Fee Overview
								</th>
							</tr>
						</thead>
						<tbody class='w3-white'>
							<tr>
								<th>Name</th>
								<td>$student->s_name</td>
							</tr>
							<tr>
								<th>Fee Amount</th>
								<td>$fee_master->fee_amt</td>
							</tr>
							<tr>
								<th>Installments</th>
								<td>$fee_master->num_of_installment</td>
							</tr>
							<tr>
								<th>Fee Paid</th>
								<td>$fee_master->fee_received</td>
							</tr>
							<tr>
								<th>Fee Due</th>
								<td>$fee_master->due_fee</td>
							</tr>
						</tbody>
					</table>
				");
                                    ?>
                                </div>
                            </div>
                            <div class='w3-col l8 m8'>
                                <div class="w3-padding">
                                    <table class="w3-table w3-border" border='1'>
                                        <thead class="w3-green">
                                            <tr>
                                                <th>S No.</th>
                                                <th>Installment</th>
                                                <th>Amount</th>
                                                <th>Due Amount</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="w3-white">
                                            <?php
                                            $query = "SELECT * FROM student_fee_option_details WHERE student_fee_option_master_id='$fee_master->id'";
                                            $res = mysqli_query($con, $query);
                                            $i = 0;
                                            while ($row = mysqli_fetch_object($res)) {
                                                $i++;
                                                $status = $row->fee_status == "fpd" ? "Full Paid" : ($row->fee_status == "ppd" ? "Partly Paid" : "Not Paid");
                                                $color = $row->fee_status == "fpd" ? "w3-white" : ($row->fee_status == "ppd" ? "w3-pale-red" : "w3-red");
                                                $icon = $row->fee_status == "fpd" ? "fa fa-check-circle" : "fa fa-exclamation-triangle";
                                                echo ("
								<tr class='w3-small'>
									<td>$i</td>
									<td>$row->installment_num</td>
									<td>$row->amount</td>
									<td>$row->due_amount</td>
									<td>$row->due_date</td>
									<td class='$color'>
										<i class='$icon w3-large'></i>
										$status
									</td>
								</tr>
							");
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include("footer.php");
} ?>