const getProvinces = async () => {
  return await $.getJSON('/api/region/provinces');
}

const getRegencies = async (provinceId) => {
  return await $.getJSON(`/api/region/provinces/${provinceId}/regencies`);
}

const getDistricts = async (provinceId, regencyId) => {
  return await $.getJSON(`/api/region/provinces/${provinceId}/regencies/${regencyId}/districts`);
}

const getVillages = async (provinceId, regencyId, districtId) => {
  return await $.getJSON(`/api/region/provinces/${provinceId}/regencies/${regencyId}/districts/${districtId}/villages`);
}

const getPostalCode = async (villageId) => {
  return await $.getJSON(`/api/region/postal-code/${villageId}`);
}