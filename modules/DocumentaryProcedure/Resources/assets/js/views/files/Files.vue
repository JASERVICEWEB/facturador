<template>
  <div>
    <div class="page-header pr-0">
      <h2>
        <a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a>
      </h2>
      <ol class="breadcrumbs">
        <li class="active"><span>EXPEDIENTES</span></li>
      </ol>
      <div class="right-wrapper pull-right">
        <div class="btn-group flex-wrap">
          <button
            type="button"
            class="btn btn-custom btn-sm mt-2 mr-2"
            @click="onCreate"
          >
            <i class="fa fa-plus-circle"></i> Nuevo
          </button>
        </div>
      </div>
    </div>
    <div class="card mb-0">
      <div class="card-header bg-info">
        <h3 class="my-0">Todos los expedientes</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-md-2 mb-3">
            <form class="form-group" @submit.prevent="onFilter">
              <div class="input-group mb-3">
                <input
                  type="text"
                  class="form-control"
                  placeholder="Filtrar por nombre"
                  v-model="filter.subject"
                />
                <div class="input-group-append">
                  <button
                    class="btn btn-outline-secondary"
                    type="submit"
                    style="border-color: #ced4da"
                  >
                    <i class="fa fa-search"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th></th>
                <th>Asunto</th>
                <th>Fecha/Hora registro</th>
                <th>Remitente</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in items"
                :key="item.id"
              >
                <td class="text-right">{{ item.id }}</td>
                <td>{{ item.subject }}</td>
                <td>{{ item.date_register }} - {{ item.time_register }}</td>
                <td>{{ item.sender.name }}</td>
                <td class="text-center">
                  <el-button
                    type="success"
                    @click="onEdit(item)"
                    :disabled="loading"
                  >
                    <i class="fa fa-edit"></i>
                  </el-button>
                  <el-button
                    type="danger"
                    @click="onDelete(item)"
                    :disabled="loading"
                  >
                    <i class="fa fa-trash"></i>
                  </el-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <ModalAddEdit
      :visible.sync="openModalAddEdit"
      @onAddItem="onAddItem"
      @onUpdateItem="onUpdateItem"
      :file="file"
    ></ModalAddEdit>
  </div>
</template>

<script>
import ModalAddEdit from "./ModalAddEdit";

export default {
  props: {
    files: {
      type: Array,
      required: true,
    },
  },
  components: {
    ModalAddEdit,
  },
  data() {
    return {
      items: [],
      file: null,
      openModalAddEdit: false,
      loading: false,
      filter: {
        name: "",
      },
      basePath: '/documentary-procedure/files'
    };
  },
  mounted() {
    this.items = this.files;
  },
  methods: {
    onFilter() {
      this.loading = true;
      const params = this.filter;
      this.$http
        .get(this.basePath, { params })
        .then((response) => {
          this.items = response.data.data;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    onDelete(item) {
      this.$confirm(
        `¿estás seguro de eliminar al elemento ${item.name}?`,
        "Atención",
        {
          confirmButtonText: "Si, continuar",
          cancelButtonText: "No, cerrar",
          type: "warning",
        }
      )
        .then(() => {
          this.$http
            .delete(`${this.basePath}/${item.id}/delete`)
            .then((response) => {
              this.$message({
                type: "success",
                message: response.data.message,
              });
              this.items = this.items.filter((i) => i.id !== item.id);
            })
            .catch((error) => {
              this.axiosError(error);
            });
        })
        .catch();
    },
    onEdit(item) {
      this.file = { ...item };
      this.openModalAddEdit = true;
    },
    onUpdateItem(data) {
      this.items = this.items.map((i) => {
        if (i.id === data.id) {
          return data;
        }
        return i;
      });
    },
    onAddItem(data) {
      this.items.unshift(data);
    },
    onCreate() {
      this.file = null;
      this.openModalAddEdit = true;
    },
  },
};
</script>
