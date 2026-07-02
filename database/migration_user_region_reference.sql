ALTER TABLE `user`
  ADD COLUMN `provinsi_id` varchar(10) DEFAULT NULL AFTER `rw`,
  ADD COLUMN `provinsi` varchar(100) DEFAULT NULL AFTER `provinsi_id`,
  ADD COLUMN `kabupaten_kota_id` varchar(10) DEFAULT NULL AFTER `provinsi`,
  ADD COLUMN `kabupaten_kota` varchar(100) DEFAULT NULL AFTER `kabupaten_kota_id`,
  ADD COLUMN `kecamatan_id` varchar(10) DEFAULT NULL AFTER `kabupaten_kota`,
  ADD COLUMN `desa_id` varchar(10) DEFAULT NULL AFTER `desa`;