import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import Breadcrumbs from '@/components/breadcrumbs';
import InputError from '@/components/input-error';
import LoadingButton from '@/components/loading-button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
   Select,
   SelectContent,
   SelectItem,
   SelectTrigger,
   SelectValue,
} from '@/components/ui/select';
import DashboardLayout from '@/layouts/dashboard/layout';
import { update as storageUpdate } from '@/routes/storage';

type StorageFormData = StorageFields & Record<string, string | boolean>;

interface Props extends SharedData {
   storage: Settings<StorageFormData>;
}

const Storage = ({ storage }: Props) => {
   const { props } = usePage<SharedData>();
   const { translate } = props;
   const { settings, input, button } = translate;

   const [storageDriver, setStorageDriver] = useState(
      () => storage.fields.storage_driver as 'local' | 's3' | 'r2',
   );

   return (
      <>
         <Breadcrumbs
            title={settings.storage_settings}
            breadcrumbs={[
               { title: 'Dashboard', href: '/dashboard' },
               { title: 'Settings' },
               { title: 'Storage Settings' },
            ]}
            className="mb-4"
         />

         <div className="md:px-3">
            <Card className="p-4 sm:p-6">
               <Form
                  {...storageUpdate.form(Number(storage.id))}
                  transform={(formData) => ({ ...storage.fields, ...formData })}
                  options={{ preserveScroll: true }}
                  className="space-y-6"
               >
                  {({ processing, errors }) => (
                     <>
                        <div>
                           <Label>{input.storage_driver} *</Label>
                           <Select
                              name="storage_driver"
                              defaultValue={storage.fields.storage_driver}
                              onValueChange={(value) =>
                                 setStorageDriver(
                                    value as 'local' | 's3' | 'r2',
                                 )
                              }
                           >
                              <SelectTrigger>
                                 <SelectValue
                                    placeholder={input.select_option}
                                 />
                              </SelectTrigger>
                              <SelectContent>
                                 <SelectItem value="local">Local</SelectItem>
                                 <SelectItem value="s3">AWS S3</SelectItem>
                                 <SelectItem value="r2">
                                    Cloudflare R2
                                 </SelectItem>
                              </SelectContent>
                           </Select>
                           <InputError message={errors.storage_driver} />
                        </div>

                        {storageDriver === 's3' && (
                           <>
                              <div>
                                 <Label>{input.aws_access_key_id} *</Label>
                                 <Input
                                    name="aws_access_key_id"
                                    defaultValue={
                                       storage.fields.aws_access_key_id || ''
                                    }
                                    placeholder={
                                       input.aws_access_key_id_placeholder
                                    }
                                 />
                                 <InputError
                                    message={errors.aws_access_key_id}
                                 />
                              </div>

                              <div>
                                 <Label>{input.secret_access_key}</Label>
                                 <Input
                                    type="password"
                                    name="aws_secret_access_key"
                                    defaultValue={
                                       storage.fields.aws_secret_access_key ||
                                       ''
                                    }
                                    placeholder={
                                       input.secret_access_key_placeholder
                                    }
                                 />
                                 <InputError
                                    message={errors.aws_secret_access_key}
                                 />
                              </div>
                              <div>
                                 <Label>{input.aws_default_region} *</Label>
                                 <Input
                                    name="aws_default_region"
                                    defaultValue={
                                       storage.fields.aws_default_region || ''
                                    }
                                    placeholder={
                                       input.aws_default_region_placeholder
                                    }
                                 />
                                 <InputError
                                    message={errors.aws_default_region}
                                 />
                              </div>
                              <div>
                                 <Label>{input.bucket_name} *</Label>
                                 <Input
                                    name="aws_bucket"
                                    defaultValue={
                                       storage.fields.aws_bucket || ''
                                    }
                                    placeholder={input.bucket_name_placeholder}
                                 />
                                 <InputError message={errors.aws_bucket} />
                              </div>
                           </>
                        )}

                        {storageDriver === 'r2' && (
                           <>
                              <div>
                                 <Label>Account ID or Access Key *</Label>
                                 <Input
                                    name="r2_access_key_id"
                                    defaultValue={
                                       storage.fields.r2_access_key_id || ''
                                    }
                                    placeholder="Enter R2 Access Key ID"
                                 />
                                 <InputError
                                    message={errors.r2_access_key_id}
                                 />
                              </div>

                              <div>
                                 <Label>Secret Access Key *</Label>
                                 <Input
                                    type="password"
                                    name="r2_secret_access_key"
                                    defaultValue={
                                       storage.fields.r2_secret_access_key || ''
                                    }
                                    placeholder="Enter R2 Secret Access Key"
                                 />
                                 <InputError
                                    message={errors.r2_secret_access_key}
                                 />
                              </div>

                              <div>
                                 <Label>Endpoint *</Label>
                                 <Input
                                    name="r2_endpoint"
                                    defaultValue={
                                       storage.fields.r2_endpoint || ''
                                    }
                                    placeholder="Enter R2 Endpoint"
                                 />
                                 <InputError message={errors.r2_endpoint} />
                              </div>

                              <div>
                                 <Label>Public URL *</Label>
                                 <Input
                                    name="r2_public_url"
                                    defaultValue={
                                       storage.fields.r2_public_url || ''
                                    }
                                    placeholder="https://<account-id>.r2.cloudflarestorage.com"
                                 />
                                 <InputError message={errors.r2_public_url} />
                              </div>

                              <div>
                                 <Label>Bucket Name *</Label>
                                 <Input
                                    name="r2_bucket"
                                    defaultValue={
                                       storage.fields.r2_bucket || ''
                                    }
                                    placeholder="Enter R2 Bucket Name"
                                 />
                                 <InputError message={errors.r2_bucket} />
                              </div>

                              <div>
                                 <Label>Region</Label>
                                 <Input
                                    name="r2_region"
                                    defaultValue={
                                       storage.fields.r2_region || 'auto'
                                    }
                                    placeholder="auto"
                                 />
                                 <InputError message={errors.r2_region} />
                              </div>
                           </>
                        )}

                        <LoadingButton
                           loading={processing}
                           className="float-end"
                        >
                           {button.save_changes}
                        </LoadingButton>
                     </>
                  )}
               </Form>
            </Card>
         </div>
      </>
   );
};

Storage.layout = (page: ReactNode) => <DashboardLayout children={page} />;

export default Storage;
