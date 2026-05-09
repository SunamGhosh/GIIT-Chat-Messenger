<?php
session_start();
if (!isset($_SESSION['s_id'])) {
    header('location:student-login.php');
    exit();
} else {
    ?>
    <?php include("header.php"); ?>

    <div class="container">
        <div id="mainContainer" class="clearfix">
            <div class="col-sm-12 col-md-1">
                <br>
            </div>
            <div class="col-sm-12 col-md-11">
                <div class="content-panel col-xs-12">
                    <h2 class="clearfix"><span class="page-title col-xs-12">Timetable<span
                                class="pull-right"><?php echo $_SESSION['course']['course_name']; ?></span></span></h2>
                    <div class="w3-margin-top" style="max-height:80vh;overflow: auto">
                        <table class="w3-table w3-striped w3-bordered w3-hoverable w3-table-form w3-small" border>
                            <thead>
                                <tr class="bg-primary text-light">
                                    <th colspan="10" class="text-center w3-large p-0"><label>Timetable</label>
                                </tr>
                                <tr>
                                    <th class="p-0">
                                        <div class="input-group">
                                            <span class="input-group-addon font-blinker rounded-0 text-secondary">
                                                START DATE
                                            </span>
                                            <input type="date" id="start_date" class="form-control rounded-0" value="">
                                            <span class="input-group-addon font-blinker rounded-0 text-secondary">
                                                END DATE
                                            </span>
                                            <input type="date" id="end_date" class="form-control rounded-0" value="">
                                            <div id="fetch_timetable"
                                                class="input-group-addon btn btn-sm btn-primary font-blinker rounded-0">
                                                FETCH
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                                <tr class="bg-primary text-light">
                                    <th class="text-center p-2"></th>
                                </tr>
                            </thead>
                        </table>
                        <table class="w3-table w3-bordered w3-table-form w3-small" border style="white-space: nowrap;">
                            <tbody id="timetable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="dn_js/jquery.js"></script>
    <script src="/student-management/js/moment.min.js"></script>
    <script>
        $(() => {
            $('#start_date').val(moment('Mon', ['dddd']).format('YYYY-MM-DD'))
            $('#end_date').val(moment('Sat', ['dddd']).add(1, 'days').format('YYYY-MM-DD'))
            $("#fetch_timetable").click()
        })
        $(document).on('click', '#fetch_timetable', () => {
            let st_dt = $('#start_date').val() || 0
            let en_dt = $('#end_date').val() || 0

            if (!st_dt || !en_dt) {
                return alert_msg('Invalid Dates provided or missing Date values!')
            }

            loader_custom_show(' generating timetable..')

            $.post(
                '/student-management/student/process.php',
                {
                    POST_TYPE: 'FETCH_TIMETABLE',
                    POST_DATA: JSON.stringify({ st_dt, en_dt })
                }).done((res) => {
                    loader_custom_hide()
                    try {
                        res = JSON.parse(res);
                    } catch (e) {
                        return alert_msg(e);
                    }
                    if (res['error'] != 0) {
                        return alert_msg(res['error']);
                    }

                    if (!Object.keys(res['data']).length) {
                        return alert_msg('No data available!')
                    }

                    let rows = ``,
                        rowSeperator = `<tr>
                                <td colspan="100" class="bg-white text-white"></td>
                            </tr>`,
                        rowColors = {
                            index: 0,
                            colors: ['info', 'warning']
                        }

                    console.log(Object.keys(res['data']))

                    // looping through each ${day}
                    Object.keys(res['data']).forEach((day) => {
                        rows += `
                    <tr>
                        <th rowspan="5" class="text-center warning text-ligh" style="vertical-align: middle"><label>${moment(day, ['YYYY-MM-DD']).format('(ddd) D/MM/YYYY')}</label></th>
                `

                        let startTimes = Object.keys(res['data'][day]),
                            startTimesLen = startTimes.length

                        // looping through each ${day} time
                        let timeTD = '', subjectTD = '', courseTD = '', facultyTD = '', linkTD = '';
                        startTimes.forEach((time) => {
                            timeTD += `
                        <th colspan="${res['data'][day][time].length}" class="text-center danger"><label>${time}-${res['data'][day][time][0]['class_end_time']}</label></th>
                    `

                            // looping through each ${day} ${time} subjects
                            res['data'][day][time].forEach((subject) => {
                                courseTD += `
                            <td class="${rowColors['colors'][rowColors.index]}"><label>${subject['course_name']} ${subject.semester_id} <span class='text-muted'>(${subject.class_start_time}-${subject.class_end_time})</span></label></td>
                        `
                                subjectTD += `
                            <td class="${rowColors['colors'][rowColors.index]}"><label>${subject.subject_name}</label></td>
                        `
                                facultyTD += `
                            <td class="${rowColors['colors'][rowColors.index]}"><label>${subject.faculty_name}</label></td>
                        `
                                if (subject.holiday == 'false') {

                                    let eventText = '', eventColor = ''
                                    if (subject.event !== 'false') {
                                        eventColor = 'w3-purple w3-text-white'
                                        eventText = `
                                 | <span class="fa fa-circle w3-text-orange"></span> 
                                <strong>${subject.event.substr(0, 15)}</strong>
                            `
                                    }

                                    // | ${subject.class_meeting_id} | ${subject.class_meeting_pwd}
                                    linkTD += `
                            <td class="${rowColors['colors'][rowColors.index]} ${eventColor}">
                                <label>
                                ${subject.class_room == '0' ?
                                        `<a href="${subject.class_link}" target="_blank" class="text-primary ${eventColor}"><span class="fa fa-link"></span> Link</a>`
                                        : `<b class="text-danger  ${eventColor}">${subject.room_name}</b>`
                                    }
                                ${eventText}
                                </label>
                            </td>
                        `
                                } else {
                                    linkTD += `
                            <td class="w3-purple">
                                <label> 
                                    <span class="fa fa-circle w3-text-green"></span> 
                                    <strong>Holiday - ${subject.holiday.substr(0, 15)}</strong>
                                </label>
                            </td>
                        `
                                }
                            })
                            rowColors.index = rowColors.index == 1 ? 0 : 1

                        }) // timeEach
                        rows += `
                        ${timeTD} </tr>
                        <tr> ${courseTD} </tr>
                        <tr> ${subjectTD} </tr>
                        <tr> ${facultyTD} </tr>
                        <tr> ${linkTD} </tr>
                        ${rowSeperator}
                    `
                        rowColors.index = 0

                    }) // dayEach

                    $("#timetable").html(rows)
                })

        })

    </script>
    <?php include("footer.php");
} ?>