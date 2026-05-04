import re

file_path = "c:/xampp/htdocs/GIITChat/faculty_portal.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

broadcast_replacement = """            <!-- Broadcast Dashboard View -->
            <div id="broadcast-dashboard-view" style="display: none; flex: 1; overflow-y: auto; background: #fff; padding: 20px;">
                <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<!-- =====code for main container start ============== -->
<div class="w3-container-fluid">
    <div class="w3-row">
        <!-- ======== Code for left side content start ===== -->
        <div class="w3-col l6 m6">
            <div class="w3-responsive w3-margin">
                <!-- ======Code for student filteration table start ===== -->
                <table class="w3-table w3-table-form w3-small w3-border" id="filter-data" border="1">
                    <tr class="table-row table-row w3-pale-red">
                        <td>
                            <label for="session">
                                Session
                            </label>
                        </td>
                        <td>
                            <label for="course">
                                Course
                            </label>
                        </td>
                        <td>
                            <label for="sem">
                                Sem/Year
                            </label>
                        </td>
                        <td>
                            <label for="roll">
                                Roll
                            </label>
                        </td>                      
                    </tr>
                    <tr>
                        <td colspan="1" style="width: 30%;">
                            <select class="w3-select w3-white" name="session" id="session">
                                <option value="0" selected disabled>Select</option>
                                <?php
                                    //--====code for fetch sessions start===---//
                                    $sessionQuery = DB_run_query("SELECT * FROM session_master WHERE session_status = 'active'");                                   
                                    if(DB_num_rows($sessionQuery) > 0){
                                        while($sessionRow = DB_fetch_assoc($sessionQuery)){
                                            $session_id = $sessionRow['session_master_id'];                               
                                            $session_name = $sessionRow['session_name'];                                         
                                            echo '<option name="session_id" id="session_id" value="'.$session_id.'">
                                                '.$session_name.'</option>';
                                        }
                                    }
                                    //--=====code for fetch sessions end=====--//
                                        ?>
                            </select>
                        </td>
                        <td colspan="1" style="width: 30%;">
                            <select class="w3-select w3-white" name="course" id="course">
                                <option value=""></option>
                            </select>
                        </td>
                        <td colspan="1" style="width: 20%;">
                            <select class="w3-select w3-white" name="sem" id="sem">
                                <option value=""></option>
                            </select>
                        </td>
                        <td colspan="1" style="width: 40%;">
                            <input class="w3-input" name="roll" id="roll" type="text" placeholder="Enter Roll">
                        </td>                       
                    </tr>
                </table>
                <!-- ======Code for student filteration table end ===== -->
            </div>

            <!-- =====code for fetch all students data start===== -->

            <div class="w3-responsive w3-margin" style="max-height: 42em;">
                <table class="w3-table w3-border w3-table-form w3-tiny" id="student-table" border="1">
                    <tr class="w3-text-bold table-row w3-pale-red">
                        <td colspan="7">
                            <label for="">
                                Select Student
                            </label>
                        </td>
                    </tr>
                    <tr class="std_data_row w3-pale-red">
                        <td class="w3-center data-column" style="width: 2em;">
                            <input class="w3-input text-center checkall-checkbox" onclick="enabledBtn()" name="checkAll"
                                id="checkAll" type="checkbox">
                        </td>
                        <td class="text-center data-column">
                            <label for="">
                                S no.
                            </label>
                        </td>
                        <td class="text-center data-column" style="width: 15%;">
                            <label for="">
                                Name
                            </label>
                        </td>

                        <td class="text-center data-column" style="width: 15%;">
                            <label for="">
                                Roll
                            </label>
                        </td>
                        <td class="text-center data-column" style="width: 15%;">
                            <label for="">
                                Course
                            </label>
                        </td>
                        <td class="text-center data-column" style="width: 20%;">
                            <label for="">
                                Session
                            </label>
                        </td>
                    </tr>
                    <!-- table body -->
                    <tbody id="table-data">

                    </tbody>

                </table>
            </div>
        </div>
        <!-- =====code for fetch all students data end===== -->

        <!-- =====Code for left side content end===== -->

        <!-- =====code for right side content start==== -->

        <div class="w3-col l6 m6">
            <div class="w3-margin">
                <!-- ===contact checkbox table start==== -->
                <table class="w3-table w3-table-form w3-small w3-border" id="contact-table" border="1">
                    <tr class="table-row w3-pale-red">
                        <td class="w3-text-bold" colspan="8">
                            <label for="">
                                Select Contact
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="data-column" style="width: 2em">
                            <input type="checkbox" class="w3-input w3-left contactCheckbox" type="checkbox"
                                name="contact" value="M">
                        </td>
                        <td>
                            <label class="contact-name" for="">
                                Mother
                            </label>
                        </td>
                        <td class="data-column" style="width: 2em">
                            <input type="checkbox" class="w3-input w3-left contactCheckbox" type="checkbox"
                                name="contact" value="S">
                        </td>
                        <td>
                            <label class="contact-name" for="">
                                Student
                            </label>
                        </td>
                        <td class="data-column" style="width: 2em">
                            <input type="checkbox" class="w3-input w3-left contactCheckbox" type="checkbox"
                                name="contact" value="F">
                        </td>
                        <td>
                            <label class="contact-name" for="">
                                Father
                            </label>
                        </td>
                        <td class="data-column" style="width: 2em">
                            <input type="checkbox" class="w3-input w3-left checkAllContact" type="checkbox"
                                name="contact" id="all" name="all" value="All">
                        </td>
                        <td>
                            <label for="">
                                All
                            </label>
                        </td>
                    </tr>
                </table>
                <!-- ===contact checkbox table end==== -->
            </div>

            <!-- ====Template select table start==== -->
            <div class="w3-margin">
                <table class="w3-table w3-border w3-table-form w3-small" border="1">
                    <tr class="table-row w3-text-bold w3-pale-red">
                        <td colspan="4">
                            <label for="">
                                Select Template
                            </label>
                        </td>
                    </tr>
                    <tr class="table-row w3-pale-red">
                        <td style="width: 30%;">
                            <label for="">
                                Module
                            </label>
                        </td>
                        <td style="width: 20%;">
                            <label for="">
                                Category
                            </label>
                        </td>
                        <td style="width: 30%;">
                            <label for="">
                                Sub Category
                            </label>
                        </td>
                        <td style="width: 30%;">
                            <label for="">
                                Template
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <select class="w3-select w3-white" name="module_dropdown" id="module_dropdown"
                                autocomplete="off">
                                <option class="form-control" value="" selected disabled>Select</option>
                                <?php
                                     /* code for fetch template module name start */

                                         /* Query for fetch all template modules name */
                                         $moduleQuery = DB_run_query("SELECT DISTINCT t1.module_id,t2.module_name FROM template_master_new AS t1 INNER JOIN module_master AS t2 ON t1.module_id = t2.module_id WHERE t1.template_status = 'active'");

                                         if(DB_num_rows($moduleQuery) > 0){
                                          while($moduleRow = DB_fetch_assoc($moduleQuery)){
                                              echo '<option value="'.$moduleRow["module_id"].'">'.$moduleRow["module_name"].'</option>';
                                          }
                                         }

                                     /* code for fetch template module name end */      
                                ?>
                            </select>
                        </td>
                        <td>
                            <select class="w3-select w3-white" name="cat_name" id="cat_name" autocomplete="off">
                                <option class="form-control" value="" selected disabled>Select</option>
                            </select>
                        </td>
                        <td>
                            <select class="w3-select w3-white" name="sub_cat_name" id="sub_cat_name">
                            </select>
                        </td>
                        <td>
                            <select class="w3-select w3-white" name="template_short_name" id="template_short_name">
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- ====Template select table end==== -->

            <!-- ====template details table start==== -->

            <div class="w3-margin">
                <table class="w3-table w3-table-form w3-tiny w3-border" border="1">
                    <tr class="table-row w3-text-bold w3-pale-red">
                        <td colspan="2">
                            <label for="">
                                Template Details
                            </label>
                            <span class="w3-right w3-padding-small" id="template_message_character_count">0</span>
                            <span class="w3-right w3-padding-small" style="padding-right: 0px; !important">Character
                                Count :</span>
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="0" style="width: 70%;">
                            <textarea class="w3-input" name="template" id="template" cols="30" rows="10"
                                placeholder="Select Template To Overview..." style="height: 36em;"></textarea>
                        </td>
                        <td class="table-row w3-pale-red">
                            <label for="">
                                Total Student
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td id="total_students">
                            <label for="">
                                00
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-row w3-pale-red">
                            <label for="">
                                Total Contacts
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td id="total_contacts">
                            <label for="">
                                00
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-row w3-pale-red">
                            <label for="">
                                WhatsApp Module
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="" id="selected_module">
                                ----
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-row w3-pale-red">
                            <label for="">
                                WhatsApp Category
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="" id="selected_category">
                                ---
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-row w3-pale-red">
                            <label for="">
                                WhatsApp Sub Category
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="" id="selected_sub_category">
                                ---
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-row w3-pale-red">
                            <label for="">
                                WhatsApp Template
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="" id="selected_template">
                                ----
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ====template details table end==== -->

            <!-- =====select image or pdf table start===== -->
            <div class="w3-margin">
                <table class="w3-table w3-table-form w3-border" border="1">
                    <tr class="table-row w3-small w3-text-bold w3-pale-red">
                        <th class="wd-50p">
                            <label for="">
                                Image Upload
                                <span class="table-row">(Max file size 1 MB.)</span>
                            </label>
                        </th>
                        <th>
                            <label for="">
                                Pdf Upload
                                <span class="table-row">(Max file size 2 MB.)</span>
                            </label>
                        </th>
                    </tr>
                </table>
                <table class="w3-table w3-border" border="1">
                    <tr class="table-row w3-small w3-text-bold">
                        <td class="wd-50p">
                            <input class="w3-input w3-white" id="wp_image_upload" accept="image/*" type="file">
                            <input class="w3-input" type="hidden" id="wp_image_upload_link" />
                        </td>
                        <td>
                            <input type="file" class="w3-input w3-white" id="wp_pdf_upload" accept="application/pdf" />
                            <input class="w3-input" type="hidden" id="wp_pdf_upload_link" />
                        </td>
                    </tr>
                </table>
            </div>
            <!-- =====select image or pdf table end===== -->

            <!-- ========Preview button start ========== -->

            <div class="w3-margin">
                <div class="w3-bar">
                    <button onclick="showModal()" type="button"
                        class="w3-bar-tem w3-btn w3-text-bold w3-border w3-right previewBtn w3-pale-red w3-round"
                        style="min-width: 20%;" name="previewBtn" disabled="true" id="previewBtn" value="1">
                        <i class="fa-solid fa-eye"></i> Preview
                    </button>
                </div>
            </div>

            <!-- ========Preview button end ========== -->
        </div>
        <!-- =====code for right side content end==== -->
    </div>
</div>
<!-- =====code for main container end ============== -->
            </div>
"""

content = re.sub(r'<!-- Broadcast Dashboard View -->\s*<div id="broadcast-dashboard-view".*?</div>\s*</div>\s*</div>\s*</div>', broadcast_replacement, content, flags=re.DOTALL)

preview_replacement = """<div class="card">
            <div id="previewModel" class="w3-modal w3-animate-zoom" style="z-index: 105; display: none;">
                <div class="w3-modal-content mt-3" style="width:40%;border-radius:5px;max-width: 530px;">
                    <div class="w3-round modal-box">
                        <div>
                            <div class="modal-header w3-pale-red">
                                <div>
                                    <h4 class="modal-title font-weight-bold" id="previewModelLable">
                                        <i class="fa-solid fa-eye fa-beat-fade"></i>
                                        Preview Messages
                                    </h4>
                                </div>
                                <button type="button" class="btn-close mr-2 btn-sm" onclick="closeModal();"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="preview_msg">

                            </div>
                            <div class="model-footer pt-2 pb-2 w3-pale-red">
                                <div class="action mx-2 float-right">
                                    <button name="sendBtn" id="sendBtn" value="1" type="button" class="btn w3-green"><i
                                            class="fa-brands fa-whatsapp"></i>
                                        Send WhatsApp</button>
                                </div>
                                <!--====== code for progress bar start ======-->
                                <div class="mt-2 ml-2 mr-2" id="progress-bar-container">
                                    <div class="progress-bar progress-bar-striped" id="progress-bar"></div>
                                    <div id="progress-text">0%</div>
                                </div>
                                <!--===== code for progress bar end ====== -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>"""

content = re.sub(r'<div class="modal fade custom-modal" id="previewModel" tabindex="-1">.*?</div>\s*</div>\s*</div>\s*</div>', preview_replacement, content, flags=re.DOTALL)

script_replacement = """<script>

/* ===function for show modal start==== */
function showModal() {
    $('#previewModel').show();
}
/* ===function for show modal end==== */

/* ===function for closee modal start===== */
function closeModal() {
    $('#previewModel').hide();
}
/* ===function for closee modal end===== */

/* ===function for close modal on outside click start=== */
const modal = document.getElementById('previewModel');
document.addEventListener('click', function(event) {
    if (event.target === modal) {
        closeModal();
    }
});

/* ===function for close modal on outside click end=== */


/* function for get student roll number through the checkbox start */
function checkBoxClicked() {
    var checkboxes = document.getElementsByName("std_roll");
    var previewBtn = document.getElementById("previewBtn");
    var isAnyChecked = false;

    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            isAnyChecked = true;
            break;
        }
    }
    if (isAnyChecked) {
        previewBtn.disabled = false;
    } else {
        previewBtn.disabled = true;
    }
}
/* function for get student roll number through the checkbox end */
/* function for enabled preview button start */
function enabledBtn() {
    var check_all = document.getElementById("checkAll");
    var previewBtn = document.getElementById("previewBtn");
    var isChecked = false;

    if (check_all.checked) {
        isChecked = true;
    } else {
        isChecked = false;
    }
    if (isChecked) {
        previewBtn.disabled = false;
    } else {
        previewBtn.disabled = true;
    }
}
/* function for enabled preview button end */

/* --code for select contact by the checkbox start-- */

// Get all checkbox items and the check all checkbox
const contactCheckbox = document.querySelectorAll('.contactCheckbox');
const contactAllCheckboxes = document.querySelector('#all');

// Add click event listener to check all checkbox
contactAllCheckboxes.addEventListener('click', function() {
    // If check all checkbox is checked, check all checkboxes
    if (this.checked) {
        contactCheckbox.forEach(function(item) {
            item.checked = true;
        });
    }
    // If check all checkbox is unchecked, uncheck all checkboxes
    else {
        contactCheckbox.forEach(function(item) {
            item.checked = false;
        });
    }
});

// Add click event listener to each checkbox item to check if all checkboxes are checked
contactCheckbox.forEach(function(item) {
    item.addEventListener('click', function() {
        // If any checkbox item is unchecked, uncheck the check all checkbox
        if (!this.checked) {
            contactAllCheckboxes.checked = false;
        }
        // Check if all checkboxes are checked
        else {
            let allChecked = true;
            contactCheckbox.forEach(function(item) {
                if (!item.checked) {
                    allChecked = false;
                }
            });
            // If all checkboxes are checked, check the check all checkbox
            if (allChecked) {
                contactAllCheckboxes.checked = true;
            }
        }
    });
});

/* --code for select contact by the checkbox end-- */

$(document).ready(function() {
    /* ---code for select student by the checkbox start--- */



    // when the "check all" checkbox is clicked
    $("#checkAll").click(function() {
        // check or uncheck all checkboxes based on the "check all" checkbox state
        $("input[name='std_roll']").prop('checked', $(this).prop('checked'));
    });

    // when any other checkbox is clicked
    $("input[name='std_roll']").click(function() {
        // check the state of all checkboxes to determine the state of the "check all" checkbox
        var allCheckboxChecked = ($("input[name='std_roll']:not('#checkAll')").length ===
            $("input[name='std_roll']:not('#checkAll'):checked").length);
        $("#checkAll").prop('checked', allCheckboxChecked);
    });

    /* ---code for select student by the checkbox end--- */

    /* code for get selected contact value start */

    var selectContact = [];
    $('#contact-table').change(function() {
        selectContact = [];
        $('input[name="contact"]:checked').each(function() {
            if ((this).value != "All") {
                selectContact.push((this).value);
            }
        });
        if (selectContact.length > 0) {
            $('#total_contacts').html(selectContact.length);
        } else {
            $('#total_contacts').html("00");
        }
    });

    
    /* code for get selected contact value end */

    /* ===code for get selected students roll number and their id start==== */
    var selected = []; // Used for store roll numbers of the student
    var students_id = []; // Used for store students id
    var totalCheckboxes; // Count total numbers of checked checkboxes

    $('#student-table').change(function() {
        selected = [];
        students_id = [];
        $("input[type='checkbox']:checked").each(function() {
            var roll = $(this).attr('data-roll');
            var s_id = $(this).attr('data-id');

            if (roll !== undefined) { // Check if the value is not undefined
                selected.push((roll));
                students_id.push(s_id);
            }
        });
        totalCheckboxes = selected.length;
        if (totalCheckboxes > 0) {
            $('#total_students').html(totalCheckboxes);
        } else {
            $('#total_students').html('00');
        }
    });

    /* ===code for get selected students roll number and their id end==== */

    /* ====Code for count length of the template start===== */
    // Attach the change event handler to the input field
    $("#template").on("change keyup", function() {
        // Get the input field's value
        var inputValue = $(this).val();

        // Calculate the length of the value (total characters)
        var charCount = inputValue.length;

        // Update the character count in the designated element
        $("#template_message_character_count").text(charCount);
    });

    /* ====Code for count length of the template end===== */

    /* ===code for set module name,category name, sub category name,template name start==== */

    /* module name  */
    $('#module_dropdown').on('change', function() {
        let module_name = $(this).find("option:selected");
        // Get the text of the selected option
        let selectedModule = module_name.text();
        $('#selected_module').html(selectedModule);
    })

    /* category name */
    $('#cat_name').on('change', function() {
        let cat_name = $(this).find("option:selected");
        // Get the text of the selected option
        let selectedCategory = cat_name.text();
        $('#selected_category').html(selectedCategory);
    });

    /* sub category */
    $('#sub_cat_name').on('change', function() {
        let sub_cat_name = $(this).find("option:selected");
        // Get the text of the selected option
        let selectedSubCategory = sub_cat_name.text();
        $('#selected_sub_category').html(selectedSubCategory);
    });

    /* Template */
    $('#template_short_name').on('change', function() {
        let template_sname = $(this).find("option:selected");
        // Get the text of the selected option
        let selectedTemplate = template_sname.text();
        $('#selected_template').html(selectedTemplate);
    });

    /* ===code for set module name,category name, sub category name,template name end==== */

    /* code for fetch all student data on page load start */

    function loadData() {
        let session_id = $('#session').val(); // Store session id
        let course_id = $('#course').val(); // Store course id
        let sem_id = $('#sem').val(); // Store semester id
        let roll_no = $('#roll').val(); // Store roll number


        let session = (session_id == null) ? "0" : session_id;
        let course = (course_id == '') ? "0" : course_id;
        let sem = (sem_id == '') ? "0" : sem_id;
        let roll = (roll_no == '') ? "0" : roll_no;
		
        $("#loader").show(); // Show loader
        $.ajax({
            url: "filter-std-data.php",
            type: "POST",
            data: {
                session: session,
                course: course,
                sem: sem,
                roll: roll
            },
            success: function(data) {
                $("#loader").hide(); // Hide loader
                var stdData = '';
                var s = 1;
                data = JSON.parse(data);
                $.each(data, function(index, value) {
                    let checkbox =
                        '<input onclick = "checkBoxClicked()" name="std_roll" data-roll="' +
                        value
                        .s_roll_no +
                        '" data-id = "' + value.s_id +
                        '" class="checkbox w3-left" type="checkbox">';
                    stdData += '<tr class="row-select">';
                    stdData +=
                        '<td class="id data-column" style="width: 0%;" colspan="1">' +
                        checkbox + " " + '</td>';
                    stdData +=
                        '<td class="serialno text-center data-column" colspan="1" style="width: 8%;">' +
                        s + " " + '</td>';
                    stdData +=
                        '<td class="name" id="name" style="white-space: nowrap;">' +
                        value.s_name +
                        '</td>';
                    stdData += '<td class="roll text-center data-column" colspan="1">' +
                        value.s_roll_no + '</td>';
                    stdData +=
                        '<td class="course text-center data-column" id="course_name" colspan="1">' +
                        value
                        .course_short_name +
                        "-" + value.course_name + '</td>';
                    stdData +=
                        '<td class="session text-center data-column" colspan="1">' +
                        value.session_name +
                        '</td>';
                    stdData += '</tr>';
                    ++s;
                    $("#table-data").html(stdData);
                });
            }
        });
    }
    loadData(); // calling a fucntion 

    /* code for fetch all student data on page load end */
	
    /* code for fetch session wise data start*/

    $('#session').on('change', function() {
        let session_id = $(this).val();
        let course_id = $('#course').val(); // Store course id
        let sem_id = $('#sem').val(); // Store semester id
        let roll_no = $('#roll').val(); // Store roll number


        let session = (session_id == null) ? "0" : session_id;
        let course = (course_id == '') ? "0" : course_id;
        let sem = (sem_id == '') ? "0" : sem_id;
        let roll = (roll_no == '') ? "0" : roll_no;			
		
            $.ajax({
                url: "filter-std-data.php",
                type: "POST",
                data: {
                    session: session,
                    course: course,
                    sem: sem,
                    roll: roll
                },
                success: function(data) {
                    $("#loader").hide();
                    var stdData = '';
                    var sno = 1;
                    // var checkbox = '<input name="std_id" class="mt-1" type="checkbox">';
                    data = JSON.parse(data);
                    if (data == '') {
                        $("#table-data").html(
                            "<tr><td colspan='5' class='found_msg text-center'>No data found!<td></tr>"
                        );
                    } else {
                        $.each(data, function(index, value) {
                            let checkbox =
                                '<input onclick = "checkBoxClicked()" name="std_roll" data-roll="' +
                                value
                                .s_roll_no +
                                '" data-id = "' + value.s_id +
                                '" class="checkbox w3-left" type="checkbox">';
                            stdData += '<tr class="row-select">';
                            stdData +=
                                '<td class="id data-column" style="width: 0%;" colspan="1">' +
                                checkbox + " " + '</td>';
                            stdData +=
                                '<td class="serialno text-center data-column" colspan="1" style="width: 8%;">' +
                                sno + " " + '</td>';
                            stdData +=
                                '<td class="name" id="name" colspan="1" style="white-space: nowrap;">' +
                                value.s_name +
                                '</td>';
                            stdData +=
                                '<td class="roll text-center data-column" colspan="1">' +
                                value.s_roll_no + '</td>';
                            stdData +=
                                '<td class="course text-center data-column" id="course_name" colspan="1">' +
                                value
                                .course_short_name +
                                "-" + value.course_name + '</td>';
                            stdData +=
                                '<td class="session text-center data-column" colspan="1">' +
                                value.session_name +
                                '</td>';
                            stdData += '</tr>';
                            ++sno;
                            $("#table-data").html(stdData);
                        });
                    }
                }
            });        
    });

   /* code for fetch session wise data end*/
	
	/* code for fetch course wise data start*/

    $('#course').on('change', function() {
        let session_id = $('#session').val();
        let course_id = $(this).val(); // Store course id
        let sem_id = $('#sem').val(); // Store semester id
        let roll_no = $('#roll').val(); // Store roll number


        let session = (session_id == null) ? "0" : session_id;
        let course = (course_id == '') ? "0" : course_id;
        let sem = (sem_id == '') ? "0" : sem_id;
        let roll = (roll_no == '') ? "0" : roll_no;			
		
            $.ajax({
                url: "filter-std-data.php",
                type: "POST",
                data: {
                    session: session,
                    course: course,
                    sem: sem,
                    roll: roll
                },
                success: function(data) {
                    $("#loader").hide();
                    var stdData = '';
                    var sno = 1;
                    // var checkbox = '<input name="std_id" class="mt-1" type="checkbox">';
                    data = JSON.parse(data);
                    if (data == '') {
                        $("#table-data").html(
                            "<tr><td colspan='5' class='found_msg text-center'>No data found!<td></tr>"
                        );
                    } else {
                        $.each(data, function(index, value) {
                            let checkbox =
                                '<input onclick = "checkBoxClicked()" name="std_roll" data-roll="' +
                                value
                                .s_roll_no +
                                '" data-id = "' + value.s_id +
                                '" class="checkbox w3-left" type="checkbox">';
                            stdData += '<tr class="row-select">';
                            stdData +=
                                '<td class="id data-column" style="width: 0%;" colspan="1">' +
                                checkbox + " " + '</td>';
                            stdData +=
                                '<td class="serialno text-center data-column" colspan="1" style="width: 8%;">' +
                                sno + " " + '</td>';
                            stdData +=
                                '<td class="name" id="name" colspan="1" style="white-space: nowrap;">' +
                                value.s_name +
                                '</td>';
                            stdData +=
                                '<td class="roll text-center data-column" colspan="1">' +
                                value.s_roll_no + '</td>';
                            stdData +=
                                '<td class="course text-center data-column" id="course_name" colspan="1">' +
                                value
                                .course_short_name +
                                "-" + value.course_name + '</td>';
                            stdData +=
                                '<td class="session text-center data-column" colspan="1">' +
                                value.session_name +
                                '</td>';
                            stdData += '</tr>';
                            ++sno;
                            $("#table-data").html(stdData);
                        });
                    }
                }
            });        
    });

   /* code for fetch course wise data end*/

	/* code for fetch semester wise data start*/

    $('#sem').on('change', function() {
        let session_id = $('#session').val();
        let course_id = $('#course').val(); // Store course id
        let sem_id = $(this).val(); // Store semester id
        let roll_no = $('#roll').val(); // Store roll number


        let session = (session_id == null) ? "0" : session_id;
        let course = (course_id == '') ? "0" : course_id;
        let sem = (sem_id == '') ? "0" : sem_id;
        let roll = (roll_no == '') ? "0" : roll_no;			
		
            $.ajax({
                url: "filter-std-data.php",
                type: "POST",
                data: {
                    session: session,
                    course: course,
                    sem: sem,
                    roll: roll
                },
                success: function(data) {
                    $("#loader").hide();
                    var stdData = '';
                    var sno = 1;
                    // var checkbox = '<input name="std_id" class="mt-1" type="checkbox">';
                    data = JSON.parse(data);
                    if (data == '') {
                        $("#table-data").html(
                            "<tr><td colspan='5' class='found_msg text-center'>No data found!<td></tr>"
                        );
                    } else {
                        $.each(data, function(index, value) {
                            let checkbox =
                                '<input onclick = "checkBoxClicked()" name="std_roll" data-roll="' +
                                value
                                .s_roll_no +
                                '" data-id = "' + value.s_id +
                                '" class="checkbox w3-left" type="checkbox">';
                            stdData += '<tr class="row-select">';
                            stdData +=
                                '<td class="id data-column" style="width: 0%;" colspan="1">' +
                                checkbox + " " + '</td>';
                            stdData +=
                                '<td class="serialno text-center data-column" colspan="1" style="width: 8%;">' +
                                sno + " " + '</td>';
                            stdData +=
                                '<td class="name" id="name" colspan="1" style="white-space: nowrap;">' +
                                value.s_name +
                                '</td>';
                            stdData +=
                                '<td class="roll text-center data-column" colspan="1">' +
                                value.s_roll_no + '</td>';
                            stdData +=
                                '<td class="course text-center data-column" id="course_name" colspan="1">' +
                                value
                                .course_short_name +
                                "-" + value.course_name + '</td>';
                            stdData +=
                                '<td class="session text-center data-column" colspan="1">' +
                                value.session_name +
                                '</td>';
                            stdData += '</tr>';
                            ++sno;
                            $("#table-data").html(stdData);
                        });
                    }
                }
            });        
    });

   /* code for fetch semester wise data end*/
	
	/* code for fetch roll number wise data start*/

    $('#roll').on('input', function() {
        let session_id = $('#session').val();
        let course_id = $('#course').val(); // Store course id
        let sem_id = $('#sem').val(); // Store semester id
        let roll_no = $(this).val(); // Store roll number


        let session = (session_id == null) ? "0" : session_id;
        let course = (course_id == '') ? "0" : course_id;
        let sem = (sem_id == '') ? "0" : sem_id;
        let roll = (roll_no == '') ? "0" : roll_no;			
		
            $.ajax({
                url: "filter-std-data.php",
                type: "POST",
                data: {
                    session: session,
                    course: course,
                    sem: sem,
                    roll: roll
                },
                success: function(data) {
                    $("#loader").hide();
                    var stdData = '';
                    var sno = 1;
                    // var checkbox = '<input name="std_id" class="mt-1" type="checkbox">';
                    data = JSON.parse(data);
                    if (data == '') {
                        $("#table-data").html(
                            "<tr><td colspan='5' class='found_msg text-center'>No data found!<td></tr>"
                        );
                    } else {
                        $.each(data, function(index, value) {
                            let checkbox =
                                '<input onclick = "checkBoxClicked()" name="std_roll" data-roll="' +
                                value
                                .s_roll_no +
                                '" data-id = "' + value.s_id +
                                '" class="checkbox w3-left" type="checkbox">';
                            stdData += '<tr class="row-select">';
                            stdData +=
                                '<td class="id data-column" style="width: 0%;" colspan="1">' +
                                checkbox + " " + '</td>';
                            stdData +=
                                '<td class="serialno text-center data-column" colspan="1" style="width: 8%;">' +
                                sno + " " + '</td>';
                            stdData +=
                                '<td class="name" id="name" colspan="1" style="white-space: nowrap;">' +
                                value.s_name +
                                '</td>';
                            stdData +=
                                '<td class="roll text-center data-column" colspan="1">' +
                                value.s_roll_no + '</td>';
                            stdData +=
                                '<td class="course text-center data-column" id="course_name" colspan="1">' +
                                value
                                .course_short_name +
                                "-" + value.course_name + '</td>';
                            stdData +=
                                '<td class="session text-center data-column" colspan="1">' +
                                value.session_name +
                                '</td>';
                            stdData += '</tr>';
                            ++sno;
                            $("#table-data").html(stdData);
                        });
                    }
                }
            });        
    });

   /* code for fetch roll number wise data end*/
	
   /* code for preview message start */

    $('#previewBtn').on('click', function() {
        let template = $('#template').val(); // Store template                           

        $.ajax({
            url: "preview_template_msg.php",
            type: "POST",
            data: {
                selected: selected,
                students_id: students_id,
                selectContact: selectContact,
                template: template,              
                totalCheckboxes: totalCheckboxes
            },
            success: function(data) {
                $('#preview_msg').html(data);
            }
        });
    });

   /* code for preview message end */

    /* code for send whatsapp start */

    $('#sendBtn').on('click', function() {
        let sendBtn = $('#sendBtn').val(); // store the value of send button id
        let template = $('#template').val();
        let session_id = $('#session').val(); // store the value of session
        let course_id = $('#course').val(); // store the value of course
        let sem_id = $('#sem').val(); // store the value of semester
        let roll = $('#roll').val(); // store the value of roll number
        let cat_id = $('#cat_name').val(); // Store template category id
        let sub_cat_id = $('#sub_cat_name').val(); // Store template sub category id
        let template_sid = $('#template_short_name')
            .val(); // Store template short name ids    


        if (template == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Template!',
                text: 'Please select your template first!'
            });
        } else {
            // Show the progress bar          
            $('#progress-bar-container').show();

            $.ajax({
                url: "sendMsg.php",
                type: "POST",
                data: {
                    selected: selected,
                    students_id: students_id,
                    selectContact: selectContact,
                    template: template,
                    totalCheckboxes: totalCheckboxes,
                    cat_id: cat_id,
                    sub_cat_id: sub_cat_id,
                    template_sid: template_sid,
                    sendBtn: sendBtn
                },
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) *
                                100);
                            $('#progress-bar').css('width', percent + '%');
                            $('#progress-text').text(percent + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(data) {
                    $('#progress-bar-container').hide(); // hide progress bar
                    /* close the preview modal after 1 sec */
                    setTimeout(() => {
                        $('#previewModel').hide();
                        $('#progress-text').html('0%');
                        $("#progress-bar").css("background-color",
                            "#f2f2f2");
                        Swal.fire('Message sent successfully!');
                    }, 1000)
					
					 setTimeout(function() {
                            Swal.close();
                        }, 5000); // 3000 milliseconds (1.5 seconds)
                }
            });
        }
    });

    /* code for send whatsapp end */ 
});
</script>"""

content = re.sub(r'/\* === Academic Broadcast Logic === \*/.*?</script>', script_replacement, content, flags=re.DOTALL)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
