import InputError from '@/components/input-error';
import LoadingButton from '@/components/loading-button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { onFileChangePreview } from '@/lib/inertia';
import { update } from '@/routes/frontend-pages';
import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';

const SettingsTab = () => {
   const { props } = usePage<EditorProps>();
   const { page } = props;

   const [bannerUrl, setBannerUrl] = useState(
      page.banner || '/assets/images/blank-image.jpg',
   );

   return (
      <Form
         {...update.form({ page: page.id })}
         transform={(formData) => ({
            ...formData,
            type: page.type,
         })}
         options={{ preserveScroll: true }}
      >
         {({ errors, processing }) => (
            <div className="space-y-5 p-5">
               <div className="grid gap-2">
                  <Label htmlFor="title">
                     Page Title <span className="text-red-500">*</span>
                  </Label>

                  <Input
                     id="title"
                     name="title"
                     type="text"
                     required
                     defaultValue={page.title}
                     placeholder="Page Title"
                  />

                  <InputError message={errors.title} />
               </div>

               <div className="grid gap-2">
                  <Label htmlFor="description">Description</Label>

                  <Textarea
                     id="description"
                     name="description"
                     rows={6}
                     defaultValue={page.description || ''}
                     placeholder="Page Description"
                     className="min-h-[100px]"
                  />

                  <InputError message={errors.description} />
               </div>

               <div className="grid gap-2">
                  <Label>Banner Image</Label>

                  <div className="relative">
                     <img
                        className="w-full rounded-md border border-gray-200"
                        src={bannerUrl}
                        alt="Page banner"
                     />

                     <label
                        htmlFor="banner"
                        className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 cursor-pointer rounded-md bg-secondary px-4 py-2 text-sm font-medium text-nowrap transition-colors hover:bg-secondary/80"
                     >
                        Upload Banner
                     </label>
                     <input
                        hidden
                        id="banner"
                        name="banner"
                        type="file"
                        accept="image/*"
                        onChange={(e) => onFileChangePreview(e, setBannerUrl)}
                     />
                  </div>

                  <InputError message={errors.banner} />
               </div>

               <div className="pt-3">
                  <LoadingButton
                     loading={processing}
                     type="submit"
                     className="w-full"
                  >
                     Save Changes
                  </LoadingButton>
               </div>
            </div>
         )}
      </Form>
   );
};

export default SettingsTab;
