var uploadDocuments = {
  template: `
        <div class="form-row">
					<div class="col-sm-12 col-lg-5">
            <div class="border table-responsive vh70-scroll" v-bind:style="">
              <div v-if="!students.length && !loading" class="text-muted w3-small p-1 border-bottom">No data available.</div>
              <div v-if="loading" class="text-muted w3-small p-1 border-bottom"><span class="spinner-border spinner-border-sm w3-small"></span> loading..</div>
              <table v-if="students.length && !loading" class="w3-table table-bordered w3-striped w3-table-form w3-small table-hover">
                <thead>
                  <tr class="bg-primary text-white">
                    <td colspan="4"><label>Student document stats</label></td>
                  </tr>
                  <tr class="bg-primary text-white">
                    <td><label>Sl</label></td>
                    <td><label>Roll</label></td>
                    <td><label>Name</label></td>
                    <td><label>Document</label></td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in students">
                    <td><label>{{index+1}}</label></td>
                    <td><label>{{row.s_roll_no}}</label></td>
                    <td><label>{{row.s_name}}</label></td>
                    <td>
                      <label v-if="row.document">
                          <span class="fas fa-check-circle text-success"></span> Uploaded
                      </label>
                      <label v-if="!row.document">
                        <span class="fas fa-times-circle text-danger"></span> Not Uploaded
                      </label>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="col-sm-12 col-lg-7">
            <div class="border table-responsive vh70-scroll" v-bind:style="">
              <div v-if="!students.length && !loading" class="text-muted w3-small p-1 border-bottom">No data available.</div>
              <div v-if="loading" class="text-muted w3-small p-1 border-bottom"><span class="spinner-border spinner-border-sm w3-small"></span> loading..</div>
              <table v-if="students.length && !loading" class="w3-table table-bordered w3-striped w3-table-form w3-small table-hover">
                <thead>
                  <tr class="bg-primary text-white">
                    <td colspan="5"><label>Upload Documents</label></td>
                  </tr>
                  <tr class="bg-primary text-white">
                    <td><label>Sl</label></td>
                    <td><label>Roll</label></td>
                    <td><label>Name</label></td>
                    <td><label>Document</label></td>
                    <td><label>Up</label></td>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    is="document-row"
                    v-for="(student, index) in students"
                    v-bind:class-data="classData"
                    v-bind:csrf="csrf"
                    v-bind:index="index" 
                    v-bind:student="student"
                    v-bind:key="student.s_id"
                  >
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
				</div> 
	`,
  components: {
    'document-row': documentRow,
  },
  props: ['csrf', 'classData'],
  data: function () {
    return {
      students: [],
      totalClasses: 0,
      loading: false,
      styleObject: {
        maxHeight: '80vh',
        overflowY: 'auto'
      }
    }
  },
  watch: {
    classData: {
      handler(val) {
        this.getStudentList();
      },
      deep: true
    },
    students: function () {
       // Calculation logic if needed
    }
  },
  methods: {
    getStudentList: function () {

      if (this.classData.session == null || this.classData.course == null || this.classData.document == null) {
        return
      }

      let POST_DATA = {
        session_id: this.classData.session,
        course_id: this.classData.course,
        document_id: this.classData.document
      }

      this.loading = true

      $.post(
        'script/documents.php', {
        POST_TYPE: 'FETCH_STUDENTS',
        POST_DATA: JSON.stringify(POST_DATA),
        POST_CSRF: this.csrf
      }
      ).done(res => {
        this.loading = false
        let response;
        try {
          response = JSON.parse(res);
        } catch (e) {
          return notify("Oops ! Unable to fetch students.", "danger");
        }

        if (response['error'] != 0) {
          return notify(response['error'], 'danger');
        }

        this.students = response.data
      })
    }
  }
}
