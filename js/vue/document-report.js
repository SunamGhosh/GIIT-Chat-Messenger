new Vue({
	el: '#app',
	data: {
		csrf: document.getElementById('csrf').value,
		loading: false, 	// Ajax Loading
		ajaxLoading: false, // Ajax loading for buttons,
		scheduleBasic: {
			sessions: [],
			courses: [],
			semesters: [],
			documents: [],
			selected: {
				session: null,
				course: null,
				semester: null,
				document: null
			}
		},
		studentList: []
	},
	components: {
		'select-input': selectInput,
		'upload-documents': uploadDocuments,
	},
	methods: {
		changeSession: function (value) {
			this.scheduleBasic.selected.session = value;
		},
		changeCourse: function (value) {
			this.scheduleBasic.selected.course = value;
		},
		changeDocument: function (value) {
			this.scheduleBasic.selected.document = value;
		},
		changeSemester: function (value) {
			this.scheduleBasic.selected.semester = value;
		},
        exportExcel: function () {
            if (!this.scheduleBasic.selected.session || !this.scheduleBasic.selected.course || !this.scheduleBasic.selected.document) {
                return notify("Please select Session, Course, and Document first!", "warning");
            }

            // Create a form to submit POST request for file download
            const data = {
                session_id: this.scheduleBasic.selected.session,
                course_id: this.scheduleBasic.selected.course,
                semester_id: this.scheduleBasic.selected.semester,
                document_id: this.scheduleBasic.selected.document
            };

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'script/documents.php';
            form.target = '_blank';

            const params = {
                POST_TYPE: 'EXPORT_EXCEL',
                POST_DATA: JSON.stringify(data),
                POST_CSRF: this.csrf
            };

            for (const key in params) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = params[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
	},
	created() {
		this.scheduleBasic.sessions.push({ id: 0, name: '-- Select Session --' });
		this.scheduleBasic.courses.push({ id: 0, name: '-- Select Course --' });
		this.scheduleBasic.semesters.push({ id: 0, name: '-- Select Semester --' });
		this.scheduleBasic.documents.push({ id: 0, name: '-- Select Document --' });

		__ajax('script/process.php', 'GET_SES_CRS', [], (err, res) => {

			if (err) {
				return alert('Some unexpected error has occurred!');
			}

			res.data.sessions.forEach(session => this.scheduleBasic.sessions.push({ id: session.session_master_id, name: session.session_name }));
			res.data.courses.forEach(course => this.scheduleBasic.courses.push({ id: course.course_master_id, name: course.course_name + ' - ' + course.course_short_name }));
			res.data.documents.forEach(document => this.scheduleBasic.documents.push({ id: document.type_id, name: document.type_name }));
			for (let i = 1; i <= 8; i++) { this.scheduleBasic.semesters.push({ id: i, name: 'Sem ' + i }); }

		});
	}
});
